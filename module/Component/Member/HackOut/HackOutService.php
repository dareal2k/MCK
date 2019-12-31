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
 * @link      http://www.godo.co.kr
 */
namespace Component\Member\HackOut;

use App;
use Bundle\Component\Godo\GodoKakaoServerApi;
use Bundle\Component\Godo\GodoPaycoServerApi;
use Bundle\Component\Mail\MailMimeAuto;
use Bundle\Component\Member\Manager;
use Bundle\Component\Member\MemberDAO;
use Bundle\Component\Member\MemberSnsDAO;
use Bundle\Component\Validator\Validator;
use DateTime;
use Exception;
use Framework\Utility\NumberUtils;
use Framework\Utility\StringUtils;
use Session;
/**
 * Class HackOutService
 * @package Bundle\Component\Member\HackOut
 * @author  yjwee
 */
class HackOutService extends \Bundle\Component\Member\HackOut\HackOutService
{
    private $memberDAO;
    private $hackOutDAO;
    /**
     * 사용자 회원 탈퇴
     *
     * @param $params
     * @param $memNo
     * @param $memId
     * @param $regIp
     *
     * @throws Exception
     */
    public function __construct($config = [])
    {
        $this->memberDAO = is_object($config['memberDAO']) ? $config['memberDAO'] : new MemberDAO();
        $this->hackOutDAO = is_object($config['hackOutDAO']) ? $config['hackOutDAO'] : new HackOutDAO();

        parent::__construct();
    }

    public function userHackOutFromMCK($memNo)
    {
        try{
        $member = $this->memberDAO->selectMember($memNo);
        
        $v = new Validator();
        $v->add('memPw', 'password', true, '{' . __('비밀번호') . '}'); // 비밀번호
        $v->add('reasonCd', '', false, '{' . __('탈퇴사유') . '}'); // 탈퇴사유
        $v->add('reasonDesc', '', false, '{' . __('남기실 말씀') . '}'); // 남기실 말씀
        $v->add('hackType', '', true, '{' . __('탈퇴구분') . '}');
        $v->add('memNo', 'number', true, '{' . __('회원번호') . '}');
        $v->add('memId', 'userId', true, '{' . __('회원아이디') . '}');
        $v->add('dupeinfo', '');
        $v->add('reasonCd', '');
        $v->add('reasonDesc', '');
        $v->add('hackDt', '', true, '{' . __('탈퇴일') . '}');
        $v->add('regIp', '', true);
        $v->add('rejoinFl', 'yn', true, '{' . __('재가입여부') . '}');
        $v->add('mallSno', 'number', true, '{' . __('상점번호') . '}');

        $params['memNo'] = $memNo;
        $params['memId'] = $member['memId'];
        $params['regIp'] = '127.0.0.1';
        $params['dupeinfo'] = $member['dupeinfo'];
        $params['hackType'] = 'directSelf';
        $params['hackDt'] = date('Y-m-d H:i:s');
        $params['rejoinFl'] = $this->_getReJoinFlag();
        $params['mallSno'] = StringUtils::strIsSet($member['mallSno'], '');
        $params['reasonCd'] = '01003004';
        $params['reasonDesc'] = '';
        /*
        $memberSession = $session->get(\Component\Member\Member::SESSION_MEMBER_LOGIN);
        if (isset($memberSession['accessToken'])) {
            $paycoApi = new GodoPaycoServerApi();
            $paycoApi->removeServiceOff($memberSession['accessToken']);

            $kakaoApi = new GodoKakaoServerApi();
            $kakaoToken = $session->get(GodoKakaoServerApi::SESSION_ACCESS_TOKEN);
            $kakaoApi->unlink($kakaoToken['access_token']);
            $this->memberSnsDAO->deleteMemberSns($params['memNo']);
        }
        */
        $this->hackOutDAO->setParams($params);
        $this->hackOutDAO->insertHackOutWithDeleteMemberByParams();
        // if ($member['email'] != '') {
        //     $this->_sendHackOutAutoMail($member);
        // }
        }
        catch(Exception $e){
            throw $e;
        }
    }

    private function _getReJoinFlag()
    {
        $policy = gd_policy('member.join');

        return ($policy['rejoinFl'] === 'n') ? 'y' : 'n';
    }
}