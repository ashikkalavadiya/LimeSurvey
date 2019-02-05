<?php

class DefaultPermission extends PluginBase {

    protected $storage = 'DbStorage';
    static protected $description = 'Add default survey permission';
    static protected $name = 'DefaultPermission';

    public function init() {
        /**
         * Here you should handle subscribing to the events your plugin will handle
         */
        $this->subscribe('beforeControllerAction', 'addUserAction');
    }

    /*
     * Below are the actual methods that handle events
     */

    public function addUserAction() {
        $event = $this->getEvent();
        if ($event->get("controller") == "admin" && $event->get("action") == "surveypermission" && $event->get("subaction") == "adduser") {
            $surveyId = sanitize_int($_GET['surveyid']);
            $userId = sanitize_int($_POST['uid']);
            $this->addPermissiondefault($surveyId, $userId);
        }
    }

    public function addPermissiondefault($surveyId, $userId) {
        
        $attributesforSurvey = array(
            "entity" => "survey",
            "permission" => "survey",
            "entity_id" => $surveyId,
            "uid" => $userId
        );

        $attributesforSurveycontent = array(
            "entity" => "survey",
            "permission" => "surveycontent",
            "entity_id" => $surveyId,
            "uid" => $userId
        );

        $attributesforSurveysetting = array(
            "entity" => "survey",
            "permission" => "surveysettings",
            "entity_id" => $surveyId,
            "uid" => $userId
        );

        $recordSurvey = Permission::model()->findAllByAttributes($attributesforSurvey);
        $recordSurveycontent = Permission::model()->findAllByAttributes($attributesforSurveycontent);
        $recordSurveysetting = Permission::model()->findAllByAttributes($attributesforSurveysetting);

        if (count($recordSurvey) == 0) {

            if (count($recordSurveycontent) == 0) {
                $aRecordsToinsertforSurveyContent = array(
                    array(
                        'entity' => 'survey',
                        'entity_id' => $surveyId,
                        'uid' => $userId,
                        'permission' => 'surveycontent',
                        'create_p' => 1,
                        'read_p' => 1,
                        'update_p' => 1,
                        'delete_p' => 1,
                        'import_p' => 1,
                        'export_p' => 1
                    )
                );
                Permission::model()->insertRecords($aRecordsToinsertforSurveyContent);
            }

            if (count($recordSurveysetting) == 0) {

                $aRecordsToinsertforSurveySetting = array(
                    array(
                        'entity' => 'survey',
                        'entity_id' => $surveyId,
                        'uid' => $userId,
                        'permission' => 'surveysettings',
                        'create_p' => 0,
                        'read_p' => 1,
                        'update_p' => 1,
                        'delete_p' => 0,
                        'import_p' => 0,
                        'export_p' => 0
                    )
                );
                Permission::model()->insertRecords($aRecordsToinsertforSurveySetting);
            }
        }
    }

}
