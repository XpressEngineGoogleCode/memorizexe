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
	function getMemorizeConfig($module, $module_srl)
	{
		// cache controll
		$oCacheHandler = &CacheHandler::getInstance('object');
		if($oCacheHandler->isSupport()){
			$cache_key = 'object_memorize_config:'.$module.'_'.$module_srl;
			$config = $oCacheHandler->get($cache_key);
		}
		
		if(!$config)
		{
			if(!$GLOBALS['__MemorizeConfig__'][$module][$module_srl])
			{
				$args->module = $module;
				$args->module_srl = $module_srl;
				$output = executeQuery('memorize.getMemorizeConfig', $args);
				$config = unserialize($output->data->config);
				//insert in cache
				if($oCacheHandler->isSupport())
				{
					if($config) $oCacheHandler->put($cache_key,$config);
				}
				$GLOBALS['__MemorizeConfig__'][$module][$module_srl] = $config;
			}
			return $GLOBALS['__MemorizeConfig__'][$module][$module_srl];
		}

		return $config;

	}

}
/* End of file memorize.model.php */
/* Location: ./modules/memorize/memorize.model.php */