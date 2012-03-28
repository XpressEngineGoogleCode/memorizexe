<?php
	/**
	 * @class  memorizeModel
	 * @author 라르게덴, 카르마
	 * @brief Model class of memorize module
	 **/
	class memorizeModel extends memorize
	{

		/**
		 * @brief Initialization
		 **/
		function init()
		{
		}

		/**
		 * @brief Return the memorize configuration of mid
		 * Manage mid configurations which depend on module
		 **/
		function getMemorizeConfig($module = 'board', $module_srl = 0)
		{
			// cache를 불러옵니다.
			if($oCache = $this->getMemorizeCache('object_memorize_config', array($module, $module_srl)))
			{
				return $oCache;
			}

			$args->module = $module;
			$args->module_srl = $module_srl;
			$output = executeQuery('memorize.getMemorizeConfig', $args);
			if(!$output->toBool())
			{
				return new Object(-1, "msg_error_occured");
			}

			$config = unserialize($output->data->config);

			// cache를 저장합니다.
			$this->setMemorizeCache('object_memorize_config', array($module, $module_srl), $config);
			$GLOBALS['__object_memorize_config__'][$module][$module_srl] = $config;

			return $config;
		}
	}
/* End of file memorize.model.php */
/* Location: ./modules/memorize/memorize.model.php */