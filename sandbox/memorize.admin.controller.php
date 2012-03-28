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
			$config = NULL;
			$obj = Context::getRequestVars();

			$config_page->use_update_page = $obj->use_update_page?$obj->use_update_page:'N';
			$config_page->use_delete_page = $obj->use_delete_page?$obj->use_delete_page:'N';
			$config_layout->use_update_layout = $obj->use_update_layout?$obj->use_update_layout:'N';
			$config_layout->use_delete_layout = $obj->use_delete_layout?$obj->use_delete_layout:'N';

			$output = $this->insertMemorizeConfig('page', 0, $config_page);
			$output = $this->insertMemorizeConfig('layout', 0, $config_layout);

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
			$module_srl = Context::get('target_module_srl');

			$config = NULL;
			$obj = Context::getRequestVars();
			//$obj = Context::gets('memorize_use_update_document','memorize_use_delete_document','memorize_use_update_comment','memorize_use_delete_comment','memorize_use_delete_file','memorize_use_message','memorize_use_email','memorize_block_del_document','memorize_block_del_comment','memorize_use_autosave');
			$config->use_update_document = $obj->memorize_use_update_document?$obj->memorize_use_update_document:'N';
			$config->use_delete_document = $obj->memorize_use_delete_document?$obj->memorize_use_delete_document:'N';
			$config->use_update_comment = $obj->memorize_use_update_comment?$obj->memorize_use_update_comment:'N';
			$config->use_delete_comment = $obj->memorize_use_delete_comment?$obj->memorize_use_delete_comment:'N';
			$config->use_delete_file = $obj->memorize_use_delete_file?$obj->memorize_use_delete_file:'N';
			$config->use_message = $obj->memorize_use_message?$obj->memorize_use_message:'N';
			$config->use_email = $obj->memorize_use_email?$obj->memorize_use_email:'N';
			$config->block_del_document = $obj->memorize_block_del_document?$obj->memorize_block_del_document:'N';
			$config->block_del_comment = $obj->memorize_block_del_comment?$obj->memorize_block_del_comment:'N';
			$config->use_autosave = $obj->memorize_use_autosave?$obj->memorize_use_autosave:'N';
			$output = $this->insertMemorizeConfig('board', $module_srl, $config);

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

		function insertMemorizeConfig($module = 'board', $module_srl = 0, $config = NULL) {
			$args->module = $module;
			$args->module_srl = $module_srl;
			$args->config = serialize($config);

			$oDB = &DB::getInstance();
			$oDB->begin();

			$output = executeQuery('memorize.deleteMemorizeConfig', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			// cache를 삭제합니다.
			$this->deleteMemorizeCache('object_memorize_config', array($module, $module_srl));

			$output = executeQuery('memorize.insertMemorizeConfig', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			// commit
			$oDB->commit();

			return $output;
		}
	}
/* End of file memorize.admin.controller.php */
/* Location: ./modules/memorize/memorize.admin.controller.php */