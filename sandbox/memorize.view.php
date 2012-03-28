<?php
	/**
	 * @class  memorizeView
	 * @author 라르게덴, 카르마
	 * @brief View class of the module memorize
	 **/

	class memorizeView extends memorize
	{

		/**
		 * @brief Initialization
		 **/
		function init()
		{
		}

		/**
		 * @brief Additional configurations for a service module
		 * Receive the form for the form used by memorize
		 **/
		function triggerDispMemorizeAdditionSetup(&$obj)
		{
			$current_module_srl = Context::get('module_srl');
			//$current_module = Context::get('module');
			$current_module = 'board';
/*
			if(!$current_module_srl && !$current_module_srls)
			{
				$current_module_info = Context::get('current_module_info');
				$current_module_srl = $current_module_info->module_srl;

				if(!$current_module_srl)
				{
					return new Object();
				}
			}
*/
			$oMemorizeModel = &getModel('memorize');

			if($current_module_srl)
			{
				$memorize_config = $oMemorizeModel->getMemorizeConfig($current_module, $current_module_srl);
			}

			//if(!isset($memorize_config->use_memorize)) $memorize_config->use_memorize = '';
			Context::set('module_config', $memorize_config);

			$oTemplate = &TemplateHandler::getInstance();
			$tpl = $oTemplate->compile($this->module_path.'tpl', 'memorize_module_config');
			$obj .= $tpl;

			return new Object();
		}
	}
/* End of file memorize.view.php */
/* Location: ./modules/memorize/memorize.view.php */