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
			if($cache = $this->getMemorizeCache('object_memorize_config', array($module, $module_srl)))
			{
				return $cache;
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
			if(!$this->setMemorizeCache('object_memorize_config', array($module, $module_srl), $config))
			{
				$GLOBALS['__object_memorize_config__'][$module][$module_srl] = $config;
			}

			return $config;
		}

		/**
		 * @brief 마지막 글의 idx
		 **/
		function getMemorizeLastIdx($content_srl = NULL)
		{
			if($content_srl == NULL)
			{
				return false;
			}

			$args->content_srl = $content_srl;
			$output = executeQuery('memorize.getMemorizeLastIdx', $args);
			if(!$output->toBool())
			{
				return new Object(-1, "msg_error_occured");
			}

			// 값이 없으면 기본값 0을 선언
			if(!$idx = $output->data->idx)
			{
				$idx = 0;
			}

			// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
			return (($idx*-1)+1) * -1;
		}

		function getMemorizeWithDocumentExtraVars($args = NULL)
		{
			if($args == NULL)
			{
				return false;
			}

			$output = executeQueryArray('memorize.getMemorizeWithDocumentExtraVars', $args);
			if(!$output->toBool())
			{
				return new Object(-1, "msg_error_occured");
			}

			if(!$output->data)
			{
				$output->data = array();
			}

			return $output->data;
		}

		function getCommentParentSrl($comment_srl = NULL)
		{
			if($args == NULL)
			{
				return false;
			}

			$args->parent_srl = $comment_srl;
			$output = executeQueryArray('memorize.getCommentParentSrl', $args);
			if(!$output->toBool())
			{
				return new Object(-1, "msg_error_occured");
			}

			if(!$output->data)
			{
				$output->data = array();
			}

			return $output->data;
		}

		function getCommentListItem($args = NULL)
		{
			if($args == NULL)
			{
				return false;
			}

			$output = executeQuery('comment.getCommentListItem', $args);
			if(!$output->toBool())
			{
				return new Object(-1, "msg_error_occured");
			}

			return $output->data;
		}
	}
/* End of file memorize.model.php */
/* Location: ./modules/memorize/memorize.model.php */