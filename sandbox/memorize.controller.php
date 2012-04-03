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
			if(!$obj->module_srl || !$obj->document_srl)
			{
				return;
			}

			// 기본 활용 변수 선언
			$module = $obj->module;
			$module_srl = $obj->module_srl;
			$document_srl = $obj->document_srl;
			$content = $obj->content;

			$oDocumentModel = &getModel('document');
			$oMemorizeModel = &getModel('memorize');

			// 모듈 설정을 가져옵니다.
			$memorize_config = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
			if($memorize_config->use_update_document != 'Y')
			{
				return;
			}

			// 아이피
			if(!isset($obj->ipaddress))
			{	// 현재 document.model.php updateDocument에 버그 같아서 이슈에 제출상태
				//$obj->ipaddress = $_SERVER['REMOTE_ADDR'];
			}

			// 카테고리
			if(!isset($obj->category_srl))
			{
				$obj->category_srl = 0;
			}

			// 댓글등록여부
			if(isset($obj->comment_status))
			{
				$obj->commentStatus = $obj->comment_status;
			}
			else
			{
				$obj->commentStatus = 'DENY';
			}

			// 트랙백
			if($obj->allow_trackback!='Y')
			{
				$obj->allow_trackback = 'N';
			}

			// 홈페이지 주소
			if(!isset($obj->homepage))
			{
				$obj->homepage = '';
			}
			elseif(!preg_match('/^[a-z]+:\/\//i',$obj->homepage))
			{
				$obj->homepage = 'http://'.$obj->homepage;
			}

			// 알림
			if($obj->notify_message != 'Y')
			{
				$obj->notify_message = 'N';	
			}

			// 패스워드
			if(isset($obj->password))
			{
				$obj->password = md5($obj->password);
			}

			// 제목이 없는 글은 본문의 내용으로 대체한다.
			if(isset($obj->title))
			{
				if($obj->title == '') $obj->title = cut_str(strip_tags($obj->content),20,'...');
				if($obj->title == '') $obj->title = 'Untitled';
			}

			// 특정 문구를 제거
			$obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

			// 관리자 그룹이 아닐경우 본문에 HackTag를 제거
			if(Context::get('is_logged'))
			{
				$logged_info = Context::get('logged_info');

				if($logged_info->is_admin != 'Y')
				{
					$obj->content = removeHackTag($obj->content);
				}
			}

			// 현재 글 수정 시 입력받은 값과 비교할 기존의 글 정보를 가져옴
			$oDocument = $oDocumentModel->getDocument($document_srl, FALSE, FALSE);
			$oldDocument = $oDocument->variables;


			// 비교 결과 차이가 있다면 $is_diff를 TRUE로 바꿈
			$is_diff = null;
			foreach($oldDocument as $key => $val)
			{
				// 비교 대상이 되는 컬럼 값
				if(!in_array($key, $this->getVersionDocument()))
				{
					continue;
				}

				// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 TRUE를 반환
				if(isset($obj->{$key}) && $val != $obj->{$key})
				{
					$is_diff[] = $key;
				}
			}

/*
*
*	확장변수(언어코드 포함)에 대한 처리를 진행 해야합니다.
*
		
		if($source_obj->get('lang_code') != Context::getLangType()) {
			// Change not extra vars but language code of the original document if document's lang_code doesn't exist.
			if(!$source_obj->get('lang_code')) {
				$lang_code_args->document_srl = $source_obj->get('document_srl');
				$lang_code_args->lang_code = Context::getLangType();
				$output = executeQuery('document.updateDocumentsLangCode', $lang_code_args);
			} else {
				$extra_content->title = $obj->title;
				$extra_content->content = $obj->content;

				$document_args->document_srl = $source_obj->get('document_srl');
				$document_output = executeQuery('document.getDocument', $document_args);
				$obj->title = $document_output->data->title;
				$obj->content = $document_output->data->content;
			}
		}
*/
			if($is_diff)
			{	// 제일 마지막에 등록된 히스토리 글의 idx 번호를 구합니다. (음수로써 가장 큰 수)
				if(!$idx = $oMemorizeModel->getMemorizeLastIdx($document_srl))
				{	// 등록된 글이 없으면 idx 기본값 0을 설정
					$idx = 0;
				}

				$args->msg = serialize($is_diff);
				// type 01은 글 수정에 대한 수행번호 입니다. 02부터는 따로 정의해서 소개하겠습니다.
//				$args->type = 01;
				$args->type = $this->memorize_type['document'];
				// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
				$args->idx = (($idx*-1)+1) * -1;
				$args->module_srl = $module_srl;
				$args->content_srl = $document_srl;
				$args->parent_srl = $module_srl;
				$args->content1 = $oldDocument['title'];
				$args->content2 = $oldDocument['content'];
				// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
				unset($oldDocument['content']);
				$args->extra_vars = serialize($oldDocument);

				// 수정시 기존에 등록되었던 글을 기록 합니다.
				$this->insertMemorizeDatas($args);
			}

		}

		/**
		 * @brief 히스토리 정보 추가
		 **/
		function insertMemorizeDatas($args) {
			// begin transaction
			$oDB = &DB::getInstance();
			$oDB->begin();

			// trigger 호출 (before) : 타 모듈 연동을 위해 선언
			$output = ModuleHandler::triggerCall('memorize.insertMemorizeDatas', 'before', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			// memory_srl 생성
			if(!$args->memory_srl)
			{
				$args->memory_srl = getNextSequence();
			}

			$output = executeQuery('memorize.insertMemorizeDatas', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return new Object(-1, "msg_error_occured");
			}

			// trigger 호출 (after) : 타 모듈 연동을 위해 선언
			$trigger_output = ModuleHandler::triggerCall('memorize.insertMemorizeDatas', 'after', $args);
			if(!$trigger_output->toBool())
			{
				$oDB->rollback();
				return $trigger_output;
			}

			// commit
			$oDB->commit();

			return $output;
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

		function getVersionDocument()
		{
			if(version_compare(__XE_VErsion__, '1.5.0', '>=')) 
			{
				return array('content', 'title', 'allow_trackback', 'status', 'is_notice', 'commentStatus', 'ipaddress', 'category_srl', 'commentStatus', 'homepage', 'notify_message', 'password');
			}
			return array('lang_code','is_notice','title','content','password','email_address','homepage','tags','extra_vars','ipaddress','allow_trackback','nofity_message','is_secret','allow_comment','lock_comment','nick_name');
		}

	}
/* End of file memorize.controller.php */
/* Location: ./modules/memorize/memorize.controller.php */