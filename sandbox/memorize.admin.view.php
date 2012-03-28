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
			// 템플릿 경로 지정
			$this->setTemplatePath($this->module_path.'tpl');$oModuleModel = &getModel('module');
			$this->module_info = $oModuleModel->getModuleConfig('memorize');
		}

		/**
		 * @brief 설정
		 **/
		function dispMemorizeAdminIndex()
		{
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