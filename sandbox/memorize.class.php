<?php
    /**
     * @class  memorize
     * @author 라르게덴, 카르마
     * @brief  memorize 모듈의 high class
     **/

    class memorize extends ModuleObject 
	{
	
        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() 
		{
            $oModuleController = &getController('module');
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before');
            $oModuleController->insertTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModuleMemorize', 'after');
            $oModuleController->insertTrigger('document.updateDocument', 'memorize', 'controller', 'triggerInsertDocumentMemorize', 'after');
			$oModuleController->insertTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocumentMemorize', 'after');
			$oModuleController->insertTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerInsertCommentMemorize', 'after');
			$oModuleController->insertTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteCommentMemorize', 'after');
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() 
		{
			$oModuleModel = &getModel('module');
			if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before')) return true;
			if(!$oModuleModel->getTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModuleMemorize', 'after')) return true;
			if(!$oModuleModel->getTrigger('document.updateDocument', 'memorize', 'controller', 'triggerInsertDocumentMemorize', 'after')) return true;
			if(!$oModuleModel->getTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocumentMemorize', 'after')) return true;
			if(!$oModuleModel->getTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerInsertCommentMemorize', 'after')) return true;
			if(!$oModuleModel->getTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteCommentMemorize', 'after')) return true;
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() 
		{
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
			if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before')) $oModuleController->insertTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before');
			if(!$oModuleModel->getTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModuleMemorize', 'after')) $oModuleController->insertTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModuleMemorize', 'after');
			if(!$oModuleModel->getTrigger('document.updateDocument', 'memorize', 'controller', 'triggerInsertDocumentMemorize', 'after')) $oModuleController->insertTrigger('document.updateDocument', 'memorize', 'controller', 'triggerInsertDocumentMemorize', 'after');
			if(!$oModuleModel->getTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocumentMemorize', 'after')) $oModuleController->insertTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocumentMemorize', 'after');
			if(!$oModuleModel->getTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerInsertCommentMemorize', 'after')) $oModuleController->insertTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerInsertCommentMemorize', 'after');
			if(!$oModuleModel->getTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteCommentMemorize', 'after')) $oModuleController->insertTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteCommentMemorize', 'after');
            return new Object(0, 'success_updated');
        }
		
		/**
         * @brief 모듈 삭제 실행
         **/
		function moduleUninstall() 
		{
			$oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
			if($oModuleModel->getTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before')) $oModuleController->deleteTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before');
			if($oModuleModel->getTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModuleMemorize', 'after')) $oModuleController->deleteTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModuleMemorize', 'after');
			if($oModuleModel->getTrigger('document.updateDocument', 'memorize', 'controller', 'triggerInsertDocumentMemorize', 'after')) $oModuleController->deleteTrigger('document.updateDocument', 'memorize', 'controller', 'triggerInsertDocumentMemorize', 'after');
			if($oModuleModel->getTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocumentMemorize', 'after')) $oModuleController->deleteTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocumentMemorize', 'after');
			if($oModuleModel->getTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerInsertCommentMemorize', 'after')) $oModuleController->deleteTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerInsertCommentMemorize', 'after');
			if($oModuleModel->getTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteCommentMemorize', 'after')) $oModuleController->deleteTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteCommentMemorize', 'after');
            return new Object();
        }


        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() 
		{
        }
    }
/* End of file memorize.class.php */
/* Location: ./modules/memorize/memorize.class.php */
