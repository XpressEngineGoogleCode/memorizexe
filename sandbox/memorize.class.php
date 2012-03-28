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
			$oModuleController->insertTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModule', 'before');
			$oModuleController->insertTrigger('document.updateDocument', 'memorize', 'controller', 'triggerUpdateDocument', 'before');
			$oModuleController->insertTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocument', 'before');
			$oModuleController->insertTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerUpdateComment', 'before');
			$oModuleController->insertTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteComment', 'before');
			$oModuleController->insertTrigger('file.deleteFile','memorize', 'controller', 'triggerDeleteFile', 'before');
			$oModuleController->insertTrigger('display', 'memorize', 'controller', 'triggerDisplay','before');

			return new Object();
		}

		/**
		 * @brief 설치가 이상이 없는지 체크하는 method
		 **/
		function checkUpdate()
		{
		}

		/**
		 * @brief 업데이트 실행
		 **/
		function moduleUpdate()
		{
		}

		/**
		 * @brief 모듈 삭제 실행
		 **/
		function moduleUninstall()
		{
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');

			if($oModuleModel->getTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before'))
			{
				$oModuleController->deleteTrigger('module.dispAdditionSetup', 'memorize', 'view', 'triggerDispMemorizeAdditionSetup', 'before');
			}

			if($oModuleModel->getTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModule', 'before'))
			{
				$oModuleController->deleteTrigger('module.deleteModule', 'memorize', 'controller', 'triggerDeleteModule', 'before');
			}

			if($oModuleModel->getTrigger('document.updateDocument', 'memorize', 'controller', 'triggerUpdateDocument', 'before'))
			{
				$oModuleController->deleteTrigger('document.updateDocument', 'memorize', 'controller', 'triggerUpdateDocument', 'before');
			}

			if($oModuleModel->getTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocument', 'before'))
			{
				$oModuleController->deleteTrigger('document.deleteDocument', 'memorize', 'controller', 'triggerDeleteDocument', 'before');;
			}

			if($oModuleModel->getTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerUpdateComment', 'before'))
			{
				$oModuleController->deleteTrigger('comment.updateCommment', 'memorize', 'controller', 'triggerUpdateComment', 'before');
			}

			if($oModuleModel->getTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteComment', 'before'))
			{
				$oModuleController->deleteTrigger('comment.deleteComment', 'memorize', 'controller', 'triggerDeleteComment', 'before');
			}

			if($oModuleModel->getTrigger('file.deleteFile','memorize', 'controller', 'triggerDeleteFile', 'before'))
			{
				$oModuleController->deleteTrigger('file.deleteFile','memorize', 'controller', 'triggerDeleteFile', 'before');
			}

			if($oModuleModel->getTrigger('display', 'memorize', 'controller', 'triggerDisplay','before'))
			{
				$oModuleController->deleteTrigger('display', 'memorize', 'controller', 'triggerDisplay','before');
			}

			return new Object();
		}

		/**
		 * @brief 캐시 파일 재생성
		 **/
		function recompileCache()
		{
		}

		/**
		 * @brief Set the memorize cache
		 **/
		function setMemorizeCache($name = 'object_memorize', $args = NULL, $cache = NULL)
		{
			if($args == NULL)
			{
				return false;
			}

			// cache controll
			$oCacheHandler = &CacheHandler::getInstance('object');

			// insert in cache
			if($oCacheHandler->isSupport())
			{
				$keys = '';
				foreach($args as $val)
				{
					$keys .= "_{$val}";
				}

				$cache_key = "{$name}:{$keys}";
				$oCacheHandler->put($cache_key, $cache);
			}
		}

		/**
		 * @brief Return the memorize cache
		 **/
		function getMemorizeCache($name = 'object_memorize', $args = NULL)
		{
			if($args == NULL)
			{
				return false;
			}

			// cache controll
			$oCacheHandler = &CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport())
			{
				$keys = '';
				$cache_globals = $GLOBALS["__{$name}__"];
				foreach($args as $val)
				{
					$keys .= "_{$val}";
					$cache_globals = $cache_globals[$val];
				}

				$cache_key = "{$name}:{$keys}";
				$cache = $oCacheHandler->get($cache_key);
			}

			if($cache)
			{
				$oCache = $cache;
			}
			elseif($cache_globals)
			{
				$oCache = $cache_globals;
			}

			if($oCache)
			{
				return $oCache;
			}

			return false;
		}

		/**
		 * @brief Delete the memorize cache
		 **/
		function deleteMemorizeCache($name = 'object_memorize', $args = NULL)
		{
			if($args == NULL)
			{
				return false;
			}

			// cache
			$oCacheHandler = &CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport())
			{
				$keys = '';
				$cache_globals = $GLOBALS["__{$name}__"];

				foreach($args as $val)
				{
					$keys .= "_{$val}";
					$cache_globals = $cache_globals[$val];
				}

				$cache_key = "{$name}:{$keys}";
				$oCacheHandler->delete($cache_key);
			}
		}
	}
/* End of file memorize.class.php */
/* Location: ./modules/memorize/memorize.class.php */