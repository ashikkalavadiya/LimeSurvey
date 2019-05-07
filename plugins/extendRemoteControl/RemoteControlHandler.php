<?php
/**
 * Handler for extendRemoteControl Plugin for LimeSurvey : add yours functions here
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2015-2016 Denis Chenu <http://sondages.pro>
 * @license GPL v3
 * @version 1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class RemoteControlHandler extends remotecontrol_handle
{
    /**
     * @inheritdoc
     * Disable webroute else json returned can be broken
     */
    public function __construct(AdminController $controller)
    {
        /* Deactivate web log */
        foreach (Yii::app()->log->routes as $route) {
            $route->enabled = $route->enabled && !($route instanceOf CWebLogRoute);
        }
        parent::__construct($controller);
    }
    /**
    * RPC Routine to get information on user from extendRemoteControl plugin
    *
    * @access public
    * @param string $sSessionKey Auth credentials
    * @return array The information on user (except password)
    */
    public function get_me($sSessionKey)
    {
        if ($this->_checkSessionKey($sSessionKey))
        {
            $oUser=User::model()->find("uid=:uid",array(":uid"=>Yii::app()->session['loginID']));
            if($oUser) // We have surely one, else no sessionkey ....
            {
                $aReturn=$oUser->attributes;
                unset($aReturn['password']);
                return $aReturn;
            }
        }
    }

    /**
    * RPC Routine to get global permission of the actual user
    *
    * @access public
    * @param string $sSessionKey Auth credentials
    * @param string $sPermission string Name of the permission - see function getGlobalPermissions
    * @param $sCRUD string The permission detailsyou want to check on: 'create','read','update','delete','import' or 'export'
    * @return bool True if user has the permission
    * @return boolean
    */
    public function hasGlobalPermission($sSessionKey,$sPermission,$sCRUD='read')
    {
        $this->_checkSessionKey($sSessionKey);
        return array(
            'permission'=>Permission::model()->hasGlobalPermission($sPermission,$sCRUD)
        );
    }

    /**
    * RPC Routine to get survey permission of the actual user
    *
    * @access public
    * @param string $sSessionKey Auth credentials
    * @param $iSurveyID integer The survey ID
    * @param $sPermission string Name of the permission
    * @param $sCRUD string The permission detail you want to check on: 'create','read','update','delete','import' or 'export'
    * @return bool True if user has the permission
    * @return boolean
    */
    public function hasSurveyPermission($sSessionKey,$iSurveyID, $sPermission, $sCRUD='read')
    {
        $this->_checkSessionKey($sSessionKey);
        return array(
            'permission'=>\Permission::model()->hasSurveyPermission($iSurveyID, $sPermission, $sCRUD),
        );
    }
    
    /**
    * RPC Routine to add user to survey
    *
    * @access public
    * @param string $clinicianUser Auth credentials
    * @param $iSurveyID integer The survey ID
    * @param $sPermission string Name of the permission
    * @param $sCRUD string The permission detail you want to check on: 'create','read','update','delete','import' or 'export'
    * @return bool True if user has the permission
    * @return boolean
    */
    public function addUserToSurvey($sSessionKey,$clinicianUser,$iSurveyID, $adminUser)
    {
        $this->_checkSessionKey($sSessionKey);
        $userId = (int)$clinicianUser['uid'];
        $adminUserId = (int)$adminUser['uid'];
        $aPermissions = array(
            'survey' => array(
                'create' => 0,
                'read' => 1,
                'update' => 0,
                'delete' => 0,
                'import' => 0,
                'export' => 0
            ),
            'surveycontent' => array(
                'create' => 1,
                'read' => 1,
                'update' => 1,
                'delete' => 1,
                'import' => 1,
                'export' => 1
            ),
            'surveysettings' => array(
                'create' => 0,
                'read' => 1,
                'update' => 1,
                'delete' => 0,
                'import' => 0,
                'export' => 0
            ),
            'tokens' => array(
                'create' => 1,
                'read' => 1,
                'update' => 1,
                'delete' => 1,
                'import' => 1,
                'export' => 1
            ),
            'responses' => array(
                'create' => 1,
                'read' => 1,
                'update' => 1,
                'delete' => 1,
                'import' => 1,
                'export' => 1
            )
        );
        
        if(\Permission::model()->hasSurveyPermission($iSurveyID, 'surveysecurity', 'create',$adminUserId)){
            $response = \Permission::model()->setPermissions($userId, $iSurveyID, 'survey', $aPermissions);
            if($response){
                $result = ["status" => $response, "message"=>"Survey Shared to ".$clinicianUser['users_name']." Successfully" ];
            }else{
                $result = ["status" => $response, "message"=>"Someting Went wrong." ];
            }
        }else{
            $result = ["status" => false, "message"=>"Check Permission on limesurvey for user ".$adminUser['users_name']];
        }
        return $result;
    }
    
    /**
     * Return the ids and info of (sub-)questions of a survey/group.
     * Returns array of ids and info with all attributes.
     *
     * @access public
     * @param string $sSessionKey Auth credentials
     * @param int $iSurveyID ID of the Survey to list questions
     * @param int $iGroupID Optional id of the group to list questions
     * @param string $sLanguage Optional parameter language for multilingual questions
     * @return array The list of questions
     */
    public function list_questions_with_attributes($sSessionKey, $iSurveyID, $iGroupID = null, $sLanguage = null) {
        if ($this->_checkSessionKey($sSessionKey)) {
            Yii::app()->loadHelper("surveytranslator");
            $iSurveyID = (int) $iSurveyID;
            $oSurvey = Survey::model()->findByPk($iSurveyID);
            if (!isset($oSurvey)) {
                return array('status' => 'Error: Invalid survey ID');
            }

            if (Permission::model()->hasSurveyPermission($iSurveyID, 'survey', 'read')) {
                if (is_null($sLanguage)) {
                    $sLanguage = $oSurvey->language;
                }

                if (!array_key_exists($sLanguage, getLanguageDataRestricted())) {
                    return array('status' => 'Error: Invalid language');
                }

                if ($iGroupID != null) {
                    $iGroupID = (int) $iGroupID;
                    $oGroup = QuestionGroup::model()->findByAttributes(array('gid' => $iGroupID));
                    $sGroupSurveyID = $oGroup['sid'];

                    if ($sGroupSurveyID != $iSurveyID) {
                        return array('status' => 'Error: IMissmatch in surveyid and groupid');
                    } else {
                        $aQuestionList = Question::model()->findAllByAttributes(array("sid" => $iSurveyID, "gid" => $iGroupID, "language" => $sLanguage));
                    }
                } else {
                    $aQuestionList = Question::model()->findAllByAttributes(array("sid" => $iSurveyID, "language" => $sLanguage));
                }

                if (count($aQuestionList) == 0) {
                    return array('status' => 'No questions found');
                }
                
                foreach ($aQuestionList as $oQuestion) {
                    $oAttributeValues = QuestionAttribute::model()->getQuestionAttributes($oQuestion->qid);
                    $aData[$oQuestion->attributes['title']] = array('preg'=>$oQuestion->attributes['preg']) + $oAttributeValues;
                }
                return $aData;
            } else {
                return array('status' => 'No permission');
            }
        } else {
            return array('status' => 'Invalid session key');
        }
    }

}
