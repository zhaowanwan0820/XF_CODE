<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 1/4/2016
 * Time: 18:56
 */

namespace iauth\components;

use iauth\helpers\Meta;

class IAuthFilter extends \CFilter
{
    public function preFilter($filterChain)
    {
        /* @var IAuthController $controller */
        $controller = $filterChain->controller;
        /* @var \CAction $action */
        $action = $filterChain->action;

        if ($this->allowActions($controller, $action)) {
            return true;
        }

        /* @var \iauth\components\IAuthWebUser $user */
        $user = \Yii::app()->iuser;
        if ($this->allowActions($controller, $action, true)) {
            if ($user->isGuest) {
                $controller->accessDenied(Meta::C_USER_NOT_LOGIN);
                return false;
            } else {
                return true;
            }
        }

        if ($this->isSkipViews($controller, $action) && !$user->getIsGuest()) {
            return true;
        }

        $code = $controller->getUniqueCode($action);
        if ($user->can($code, true)) {
            return true;
        } else {
            $controller->accessDenied($user->getErrCode());
            return false;
        }
    }

    /**
     * Allow Always Action
     * @param IAuthController $controller
     * @param \CAction $action
     * @param bool $needLogin
     * @return bool
     */
    public function allowActions($controller, $action, $needLogin = false)
    {
        if ($needLogin) {
            $allowActions = $controller->allowActionsOnLogin();
        } else {
            $allowActions = $controller->allowActions();
        }
        $allowActions = array_map('strtolower', $allowActions);
        return in_array(strtolower($action->id), $allowActions);
    }

    /**
     * 判断是否 View Request ( Allow View Only )
     * @param IAuthController $controller
     * @param \CAction $action
     * @return bool
     */
    public function isSkipViews($controller, $action)
    {
        $skipViews = array_map('strtolower', $controller->skipViews());
        if (in_array(strtolower($action->id), $skipViews)) {
            if (!($controller->isPost || $controller->expectJson)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function postFilter($filterChain)
    {
        return true;
    }
}
