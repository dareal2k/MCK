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
namespace Controller\Front\Member;

// use Bundle\Component\Godo\GodoPaycoServerApi;
// use Bundle\Component\Member\HackOut\HackOutService;
// use Bundle\Component\Member\Member;
// use Bundle\Component\Member\MemberSnsService;
use Component\Member\HackOut\HackOutService;
use Exception;
use Request;
use Session;

class HackOutMckController  extends \Controller\Front\Controller
{
    public function index()
    {
        $request = \App::getInstance('request');
        try {
            $memNo =  $request->post()->get('memNo');
            $hackOutService = new HackOutService();
            $hackOutService->userHackOutFromMCK($memNo);

            $this->js('location.href="http://mashop.co.kr";');
          
        } catch (Exception $e) {
            // throw new AlertOnlyException($e->getMessage());
        }
    }
}