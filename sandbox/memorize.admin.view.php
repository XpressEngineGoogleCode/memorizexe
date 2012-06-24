<?php
	/**
	 * @class  memorizeAdminView
	 * @author 라르게덴, 카르마
	 * @brief View class of the module memorize
	 **/

	class memorizeAdminView extends memorize
	{

		/**
		 * @brief Initialization
		 **/
		function init()
		{
			$oModuleModel = &getModel('module');
			$this->module_info = $oModuleModel->getModuleConfig('memorize');

			// 템플릿 경로 지정
			$this->setTemplatePath($this->module_path.'tpl');
		}

		/**
		 * @brief 설정
		 **/
		function dispMemorizeAdminIndex()
		{
			$oMemorizeModel = &getModel('memorize');
			$config_page = $oMemorizeModel->getMemorizeConfig('page', 0);
			$config_layuout = $oMemorizeModel->getMemorizeConfig('layout', 0);

			$this->module_info->use_update_page = $config_page->use_update_page;
			$this->module_info->use_delete_page = $config_page->use_delete_page;
			$this->module_info->use_update_layout = $config_layuout->use_update_layout;
			$this->module_info->use_delete_layout = $config_layuout->use_delete_layout;

			Context::set('module_info',$this->module_info);
			$this->setTemplateFile('index');
		}

		/**
		 * @brief 기록
		 **/
		function dispMemorizeAdminList()
		{
			$this->setTemplateFile('list');
		}

		/**
		 * @brief 로그
		 **/
		function dispMemorizeAdminLog()
		{
			$this->setTemplateFile('log');
		}
	}
/* End of file memorize.admin.view.php */
/* Location: ./modules/memorize/memorize.admin.view.php */