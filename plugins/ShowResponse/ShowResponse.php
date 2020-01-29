<?php
class ShowResponse extends PluginBase {
    protected $storage = 'DbStorage';    
    static protected $description = 'Demo: handle a survey response';
    static protected $name = 'Show response';
    
    public function init()
    {
        /**
         * Here you should handle subscribing to the events your plugin will handle
         */
        $this->subscribe('afterSurveyComplete', 'showTheResponse');
    }
    
    /*
     * Below are the actual methods that handle events
     */
    public function showTheResponse() 
    {
        $event      = $this->getEvent();        
        $surveyId   = $event->get('surveyId');
        $responseId = $event->get('responseId');
        $response   = $this->pluginManager->getAPI()->getResponse($surveyId, $responseId);

        $data = [
            'survey_id' => $surveyId,
            'response_id' => $responseId,
            'response_data' => json_encode($response)
        ];
        
        if (Yii::app()->user) {
            $users = User::model()->findByPk(Yii::app()->user->id);
            $data['user_id'] = Yii::app()->user->id;
            $data['user_name'] = $users->full_name; 
        }
        // Curl call to save response directly to worddiagnostic manager site.
        $output = Yii::app()->curl->post('http://manager.worddiagnostics.com/limesurvey-api/add-response', $data);
        $event->getContent($this);
    }    
}
