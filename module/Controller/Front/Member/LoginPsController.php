<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */
namespace Controller\Front\Member;

/**
 * Class LoginPsController
 * @package Bundle\Controller\Front\Member
 * @author  yjwee
 */

use App;
use Request;
use Session;
use Component\Member\History;
use Component\Attendance\AttendanceCheckLogin;
use Component\Member\Exception\LoginException;
use Component\Member\Util\MemberUtil;
use Component\SiteLink\SiteLink;
use Component\Member\MyPage;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;
use Framework\Object\DotNotationSupportStorage;
use Framework\Object\SimpleStorage;

class LoginPsController extends \Bundle\Controller\Front\Member\LoginPsController
{
    public function index()
    {
        $isNewMember = false;
        $isSSO = Request::post()->get("isSSO", 'N');
        if($isSSO == 'N'){
            parent::index();
        }
        else{
            MemberUtil::logout();

            $member = \App::load('\\Component\\Member\\Member');
            $postValue = Request::post()->xss()->all();
            $memberVO = $member->getMember($postValue["memId"], 'memId');

            // member 정보가 없을 경우 강제 회원 등록 처리
            // member 정보가 있을 경우 회원 정보 업데이트
            if($memberVO == null){
                $memberVO = $member->join($postValue);
                $isNewMember = true;
            }
            $returnUrl = urldecode(MemberUtil::getLoginReturnURL());

            $siteLink = new SiteLink();
            $returnUrl = $siteLink->link($returnUrl);

            $memId = $postValue["memId"];
            $memPw = $postValue["memPw"];
            
            $member->login($memId, $memPw);
            $storage = new SimpleStorage(Request::post()->all());
            MemberUtil::saveCookieByLogin($storage);
            $directMsg = 'parent.location.href=\'' . $returnUrl . '\'';
            if(!$isNewMember)
            {
                if (MemberUtil::isLogin()) {
                    try {
                        \DB::begin_tran();
                        $check = new AttendanceCheckLogin();
                        $message = $check->attendanceLogin();
                        \DB::commit();
                        $msg = 'var msg=\'' . $message . '\'; alert(\'' . $message . '\');parent.location.href=\'' . $returnUrl . '\';';
                        // 에이스 카운터 로그인 스크립트
                        $acecounterScript = \App::load('\\Component\\Nhn\\AcecounterCommonScript');
                        $acecounterUse = $acecounterScript->getAcecounterUseCheck();
                        if($acecounterUse) {
                            $returnScript = $acecounterScript->getLoginScript();
                            echo $returnScript;
                            $msg = 'setTimeout(function(){ var msg=\''. $message. '\'; alert(\'' . $message . '\'); parent.location.href=\''. $returnUrl . '\';}, 200);';
                            $directMsg = 'setTimeout(function(){ parent.location.href=\'' . $returnUrl . '\'; }, 200);';
                        }

                        if ($message) {
                            $this->js($msg);
                        }
                    } catch (\Exception $e) {
                        \DB::rollback();
                        $logger->info(__METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage());
                    }
                }

                $myPage = \App::load('\\Component\\Member\\MyPage');
                $beforeSession = Session::get($member::SESSION_MEMBER_LOGIN);
                $requestParams = $postValue;

                // 비밀번호 변경은 제외한다.
                $requestParams["memPw"] = '';
                //회원 번호는 세션에 저장 되어 있는 회원 번호로 가져옴
                // $requestParams['memNo'] = Session::get(MyPage::SESSION_MY_PAGE_MEMBER_NO, 0);
                $requestParams['memNo'] = $memberVO['memNo'];
                $beforeMemberInfo = $myPage->getDataByTable(DB_MEMBER, $requestParams['memNo'], 'memNo');
                $beforeSession['recommId'] = $beforeMemberInfo['recommId'];
                $beforeSession['recommFl'] = $beforeMemberInfo['recommFl'];

                // 회원정보 이벤트
                $modifyEvent = \App::load('\\Component\\Member\\MemberModifyEvent');
                $mallSno = \SESSION::get(SESSION_GLOBAL_MALL)['sno'] ? \SESSION::get(SESSION_GLOBAL_MALL)['sno'] : DEFAULT_MALL_NUMBER;
                $activeEvent = $modifyEvent->getActiveMemberModifyEvent($mallSno, 'life');
                $memberLifeEventCnt = $modifyEvent->checkDuplicationModifyEvent($activeEvent['sno'], $requestParams['memNo'], 'life'); // 이벤트 참여내역
                $getMemberLifeEventCount = $modifyEvent->getMemberLifeEventCount($requestParams['memNo']); // 평생회원 변경이력

                try {
                    Session::set($member::SESSION_MODIFY_MEMBER_INFO, $beforeMemberInfo);
                    \DB::begin_tran();
                    $myPage->modify($requestParams, $beforeSession);
                    $history = new History();
                    $history->setMemNo($requestParams['memNo']);
                    $history->setProcessor('member');
                    $history->setProcessorIp(Request::getRemoteAddress());
                    $history->initBeforeAndAfter();
                    $history->addFilter(array_keys($requestParams));
                    $history->writeHistory();
                    \DB::commit();
                } catch (Exception $e) {
                    \DB::rollback();
                    throw $e;
                }
                $myPage->sendEmailByPasswordChange($requestParams, Session::get($member::SESSION_MEMBER_LOGIN));
                $myPage->sendSmsByAgreementFlag($beforeSession, Session::get($member::SESSION_MEMBER_LOGIN));

                // 회원정보 수정 이벤트
                $afterSession = Session::get($member::SESSION_MEMBER_LOGIN);
                if (strtotime($afterSession['changePasswordDt']) > strtotime($beforeSession['changePasswordDt'])) {
                    $requestParams['changePasswordFl'] = 'y';
                }
                $resultModifyEvent = $modifyEvent->applyMemberModifyEvent($requestParams, $beforeMemberInfo);
                if (empty($resultModifyEvent['msg']) == false) {
                    $msg = 'alert("' . $resultModifyEvent['msg'] . '");';
                }

                // 평생회원 이벤트
                if (!$memberLifeEventCnt && $getMemberLifeEventCount == 0 && $requestParams['expirationFl'] === '999') {
                    $resultLifeEvent = $modifyEvent->applyMemberLifeEvent($beforeMemberInfo, 'life');
                    if (empty($resultLifeEvent['msg']) == false) {
                        $msg = 'alert("' . $resultLifeEvent['msg'] . '");';
                    }
                }
                // $sitelink = new SiteLink();
                // $returnUrl = $sitelink->link(Request::getReferer());
            }
            $this->js($directMsg);
        }
    }
}