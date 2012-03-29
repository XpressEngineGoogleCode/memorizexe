<?php
/**
 * @class  memorizeController
 * @author 라르게덴, 카르마
 * @brief Controller class of the module memorize
 **/

class memorizeController extends memorize
{

	/**
	 * @brief Initialization
	 **/
	function init()
	{
	}

	/**
	 * @brief 모듈 제거
	 **/
	function triggerDeleteModule(&$obj)
	{
	}

	/**
	 * @brief 본문 수정
	 **/
	function triggerUpdateDocument(&$obj)
	{
		$document_srl = $obj->document_srl;
		$module_srl = $obj->module_srl;
		if(!$module_srl) return;
		$module = $obj->module;
		$content = $obj->content;
/*
		if(!$module)
		{
			$oModuleModel = &getModel('module');
			$module_info = $mOmduleModel->getModuleInfoByModuleSrl($module_srl);
			$module = $module_info->module;
		}
		if(!$module) return;
*/
		$oMemorizeModel = &getModel('memorize');
		$memorize_config = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
		if($memorize_config->use_update_document != 'Y') return;

		$oDocumentModel = &getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl, false, false);
		$oldDocument = $oDocument->variables;
		if($content != $oldDocument['content'])
		{
/*
			//여기 변수처리...
			
			$oDB = &DB::getInstance();
			$oDB->begin();
			$output = $oMemorizeModel->insertMemorizeDatas($args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
*/
		}

	}

	/**
	 * @brief 본문 삭제
	 **/
	function triggerDeleteDocument(&$obj)
	{
	}

	/**
	 * @brief 댓글 수정
	 **/
	function triggerUpdateComment(&$obj)
	{
	}

	/**
	 * @brief 댓글 삭제
	 **/
	function triggerDeleteComment(&$obj)
	{
	}

	/**
	 * @brief 첨부파일 삭제
	 **/
	function triggerDeleteFile(&$obj)
	{
	}

	/**
	 * @brief Display
	 **/
	function triggerDisplay(&$obj)
	{
	}
}
/* End of file memorize.controller.php */
/* Location: ./modules/memorize/memorize.controller.php */