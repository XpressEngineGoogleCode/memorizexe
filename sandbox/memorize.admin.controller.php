<?php
	/**
	 * @class  memorizeAdminController
	 * @author 라르게덴, 카르마
	 * @brief Controller class of the module memorize
	 **/

	class memorizeAdminController extends memorize
	{

		/**
		 * @brief Initialization
		 **/
		function init()
		{
		}

		/**
		 * @brief 설정
		 **/
		function procMemorizeAdminConfig()
		{
			$oModuleController = &getController('module');
			$config = Context::gets('use_update_page','use_delete_page','use_update_layout','use_delete_layout');
			
			$oModuleController->insertModuleConfig('memorize', $config);
			$this->setMessage("success_saved");

			if(Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMemorizeAdminIndex'));
			}
		}

		/**
		 * @brief 모듈별 설정
		 **/
		function procMemorizeAdminInsertModuleConfig()
		{
			$this->setMessage("success_saved");

			if(Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminBoardAdditionSetup', 'module_srl', Context::get('target_module_srl')));
			}
		}
	}
/* End of file memorize.admin.controller.php */
/* Location: ./modules/memorize/memorize.admin.controller.php */