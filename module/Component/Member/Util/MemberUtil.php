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
namespace Component\Member\Util;

/**
 * Class MemberUtil
 * @package Bundle\Component\Member\Util
 * @author  yjwee
 * @method static MemberUtil getInstance
 */
class MemberUtil extends \Bundle\Component\Member\Util\MemberUtil
{
    public static function getMemPwdBySSOLogin($params){
        // $password = 'MCK' . substr(gd_remove_special_char($params['cellPhone']),3,8) . '!@#';
        $mail = explode('@', $params['email']);
        $emailPassword;
        if(strlen($mail[0]) > 10)
            $emailPassword = substr($mail[0],0,9);
        else
            $emailPassword = $mail[0];
        $password = 'MCK' . $emailPassword . '!@#';
        return $password;
    }
}