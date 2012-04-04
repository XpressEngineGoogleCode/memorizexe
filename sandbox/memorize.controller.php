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

			$oDocumentModel = &getModel('document');
			$oMemorizeModel = &getModel('memorize');

			// 기본 활용 변수 선언
			$module = $obj->module;
			$module_srl = $obj->module_srl;
			$document_srl = $obj->document_srl;

			// 비교결과를 담는다.
			$is_diff = NULL;

			// 기록할 확장변수의 정보를 담는다. 
			$args_diff = NULL;

			// 넘어온 값을 우선 배열에 담는다.
			$args = $obj;

			// 모듈 설정을 가져옵니다.
			$memorize_config = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
			if($memorize_config->use_update_document != 'Y')
			{
				return;
			}

			// 아이피
			if(!isset($obj->ipaddress))
			{	// 현재 document.model.php updateDocument에 버그 같아서 이슈에 제출상태
				//$args->ipaddress = $_SERVER['REMOTE_ADDR'];
			}

			// 카테고리
			if(!isset($obj->category_srl))
			{
				$args->category_srl = 0;
			}

			// 댓글등록여부
			if(isset($obj->comment_status))
			{
				$args->commentStatus = $obj->comment_status;
			}
			else
			{
				$args->commentStatus = 'DENY';
			}

			// 트랙백
			if($obj->allow_trackback != 'Y')
			{
				$args->allow_trackback = 'N';
			}

			// 홈페이지 주소
			if(!isset($obj->homepage))
			{
				$args->homepage = '';
			}
			elseif(!preg_match('/^[a-z]+:\/\//i', $obj->homepage))
			{
				$args->homepage = "http://{$obj->homepage}";
			}

			// 알림
			if($obj->notify_message != 'Y')
			{
				$args->notify_message = 'N';	
			}

			// 패스워드
			if(isset($obj->password))
			{
				$args->password = md5($obj->password);
			}

			// 제목이 없는 글은 본문의 내용으로 대체한다.
			if(isset($obj->title))
			{
				if($obj->title == '') $args->title = cut_str(strip_tags($obj->content), 20, '...');
				if($obj->title == '') $args->title = 'Untitled';
			}

			// 특정 문구를 제거
			$args->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

			// 관리자 그룹이 아닐경우 본문에 HackTag를 제거
			if(Context::get('is_logged'))
			{
				$logged_info = Context::get('logged_info');

				if($logged_info->is_admin != 'Y')
				{
					$args->content = removeHackTag($args->content);
				}

				$args->member_srl = $logged_info->member_srl;
			}
			else
			{
				$args->member_srl = 0;
			}

			// 현재 글 수정 시 입력받은 값과 비교할 기존의 글 정보를 가져옴
			$oDocument = $oDocumentModel->getDocument($document_srl, FALSE, FALSE);
			$oldDocument = $oDocument->variables;

			// 기존에 등록된 언어와 현재 설정된 언어가 다른지 확인
			if($oldDocument['lang_code'] != Context::getLangType())
			{
				$is_lang = TRUE;
			}

			/*
			 * xe_documents의 글을 비교한다.
			*/
			foreach($oldDocument as $key => $val)
			{
				// 기존에 등록된 언어와 현재 설정된 언어가 다르다면 title, content는 비교하지 않는다.
				if($is_lang && in_array($key, array('title', 'content')))
				{
					continue;
				}

				// 비교 대상이 되는 컬럼 값
				if(!in_array($key, $this->getVersionDocument()))
				{
					continue;
				}

				// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 해당 컬럼을 배열에 담는다.
				if(isset($obj->{$key}) && $val != $obj->{$key})
				{
					$is_diff->{$key} = TRUE;
				}
			}

			// 기존에 등록된 언어와 현재 설정된 언어가 다르다면 확장변수에서 찾는다
			if($is_lang)
			{
				// 확장변수 중 언어별 타이틀(-1)과 본문(-2)을 가져온다.
				$obj_extra_var->document_srl = $document_srl;
				$obj_extra_var->lang_code = Context::getLangType();
				$obj_extra_var->var_idx = array(-1, -2);
				$oExtraLang = $oMemorizeModel->getMemorizeWithDocumentExtraVars($obj_extra_var);

				/*
				 * xe_document_extra_vars에서 언어별 타이틀, 본문을 비교한다.
				*/
				foreach($oExtraLang as $val)
				{
					// 언어별 타이틀을 비교하여 결과가 다르다면 해당 컬럼을 배열에 담고 해당 내용을 기록
					if($val->var_idx == -1 && $val->value != $obj->title)
					{
						$is_diff->extra_var_title = TRUE;

						// type의 글 수정에 대한 수행번호를 선언(언어별 확장변수)
						$args_extra_lang->type = $this->memorize_type['lang'];
						$args_extra_lang->content1 = $val->value;
						$args_extra_lang->content2 = $val->var_idx;
						// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 컬럼은 제거 합니다.
						unset($val->value);
						$args_extra_lang->extra_vars = serialize($val);

						$args_diff->extra_var_title = $args_extra_lang;
						unset($args_extra_lang);
					}
					// 언어별 본문을 비교하여 결과가 다르다면 해당 컬럼을 배열에 담고 해당 내용을 기록
					elseif($val->var_idx == -2 && $val->value != $obj->content)
					{
						$is_diff->extra_var_content = TRUE;

						// type의 글 수정에 대한 수행번호를 선언
						$args_extra_lang->type = $this->memorize_type['lang'];
						$args_extra_lang->content1 = $val->value;
						$args_extra_lang->content2 = $val->var_idx;
						// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 컬럼은 제거 합니다.
						unset($val->value);
						$args_extra_lang->extra_vars = serialize($val);

						$args_diff->extra_var_content = $args_extra_lang;
						unset($args_extra_lang);
					}
				}
			}

			// 확장변수 중 언어별 데이터를 가져온다.
			$obj_extra_var->not_var_idx = $args_extra_var->var_idx;
			// 확장변수를 불러오기 위해 언어별 타이틀, 본문에 사용되었던 변수를 제거
			unset($obj_extra_var->var_idx);
			$oExtraVars = $oMemorizeModel->getMemorizeWithDocumentExtraVars($obj_extra_var);

			/*
			 * xe_document_extra_vars에서 언어별 타이틀, 본문을 제외한 확장변수를 비교한다.
			*/
			foreach($oExtraVars as $val)
			{
				// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 해당 컬럼을 배열에 담는다.
				if(isset($obj->{"extra_vars{$val->var_idx}"}) && $val->value != $obj->{"extra_vars{$val->var_idx}"})
				{
					$is_diff->{"extra_vars{$val->var_idx}"} = TRUE;

					// type의 글 수정에 대한 수행번호를 선언
					$args_extra_var->type = $this->memorize_type['extra_vars'];
					$args_extra_var->content1 = $val->value;
					$args_extra_var->content2 = $val->var_idx;
					// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 컬럼은 제거 합니다.
					unset($val->value);
					$args_extra_var->extra_vars = serialize($val);

					$args_diff->{"extra_vars{$val->var_idx}"} = $args_extra_var;
					unset($args_extra_var);
				}
			}

			// 비교한 컬럼 정보와 함께 xe_documents의 글을 기록한다.
			if(count($is_diff) >= 1)
			{
				// type의 글 수정에 대한 수행번호를 선언
				$args->type = $this->memorize_type['document'];
				// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
				$args->idx = $oMemorizeModel->getMemorizeLastIdx($document_srl);
				$args->module_srl = $module_srl;
				$args->content_srl = $document_srl;
				$args->parent_srl = $module_srl;
				$args->content1 = $oldDocument['title'];
				$args->content2 = $oldDocument['content'];
				// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
				unset($oldDocument['content']);
				$args->extra_vars = serialize($oldDocument);

				// 수정시 기존에 등록되었던 글을 기록 합니다.
				$oMemorizeDatas = $this->insertMemorizeDatas($args);
				$memory_srl = $oMemorizeDatas->variables['memory_srl'];

				// 확장변수 정보를 기록(언어별 글, 확장변수)
				if(count($args_diff) >= 1)
				{
					foreach($args_diff as $args_val)
					{
						$args_val->idx = $oMemorizeModel->getMemorizeLastIdx($document_srl);
						$args_val->module_srl = $module_srl;
						$args_val->content_srl = $document_srl;
						// 부모를 글 정보의 memory_srl로 지정
						$args_val->parent_srl = $memory_srl;
						$this->insertMemorizeDatas($args_val);
					}
				}

				// 기록된 글의 sequence번호를 기록
				$args->memory_srl = $memory_srl;
				// 로그 기록 형식을 수정사항으로 기록
				$args->code = $this->memorize_code['update'];
				// 비교 결과 값이 다른 컬럼을 정리한다. 
				$args->diff_column = serialize($is_diff);

				// 수정시 로그를 기록합니다.
				$this->insertMemorizeLog($args);
			}
		}

		/**
		 * @brief 히스토리 로그 추가
		 **/
		function insertMemorizeLog($args = NULL)
		{
			if($args == NULL)
			{
				return new Object(-1, "msg_error_occured");
			}

			// 회원이면 member_srl을 추가...
			if($logged_info = Context::get('logged_info'))
			{
				$args->member_srl = $logged_info->member_srl;
			}

			// begin transaction
			$oDB = &DB::getInstance();
			$oDB->begin();

			// trigger 호출 (before) : 타 모듈 연동을 위해 선언
			$output = ModuleHandler::triggerCall('memorize.insertMemorizeLog', 'before', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			$output = executeQuery('memorize.insertMemorizeLog', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return new Object(-1, "msg_error_occured");
			}

			// trigger 호출 (after) : 타 모듈 연동을 위해 선언
			$trigger_output = ModuleHandler::triggerCall('memorize.insertMemorizeLog', 'after', $args);
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
		 * @brief 히스토리 정보 추가
		 **/
		function insertMemorizeDatas($args = NULL)
		{
			if($args == NULL)
			{
				return new Object(-1, "msg_error_occured");
			}

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

			// memory_srl 리턴
			$output->add('memory_srl', $args->memory_srl);

			return $output;
		}

		/**
		 * @brief 본문 삭제
		 **/
		function triggerDeleteDocument(&$obj)
		{
			//삭제때는 달랑 이거 하나 넘어오넹~~
			$document_srl = $obj->document_srl;

			//문서번호에서 module_info 가져오기
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
			$module = $module_info->module;
			$module_srl = $module_info->module_srl;

			$oDocumentModel = &getModel('document');
			$oMemorizeModel = &getModel('memorize');

			// 모듈 설정을 가져옵니다.
			$memorize_config = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
			if($memorize_config->use_delete_document != 'Y')
			{
				return;
			}

			$oDocument = $oDocumentModel->getDocument($document_srl, FALSE, FALSE);
			$oldDocument = $oDocument->variables;

			//저장할 변수처리
			$args->type = $this->memorize_type['document'];
			if(!$idx = $oMemorizeModel->getMemorizeLastIdx($document_srl))
			{	// 등록된 글이 없으면 idx 기본값 0을 설정
				$idx = 0;
			}

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

			$oMemorizeDatas = $this->insertMemorizeDatas($args);
			$memory_srl = $oMemorizeDatas->variables['memory_srl'];

			// 기록된 글의 sequence번호를 기록
			$args->memory_srl = $memory_srl;
			// 로그 기록 형식을 수정사항으로 기록
			$args->code = $this->memorize_code['delete'];

			// 수정시 로그를 기록합니다.
			$this->insertMemorizeLog($args);
		}

		/**
		 * @brief 댓글 수정
		 **/
		function triggerUpdateComment(&$obj)
		{
			if(!$obj->module_srl || !$obj->document_srl || !$obj->comment_srl)
			{
				return;
			}

			$module_srl = $obj->module_srl;

			//module_srl에서 module_info 가져오기
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			$module = $module_info->module;

			// 비교결과를 담는다.
			$is_diff = NULL;

			// 기록할 확장변수의 정보를 담는다.
			$args_diff = NULL;

			// 넘어온 값을 우선 배열에 담는다.
			$args = $obj;

			// 모듈 설정을 가져옵니다.
			$oMemorizeModel = &getModel('memorize');
			$memorize_config = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
			if($memorize_config->use_update_comment != 'Y')
			{
				return;
			}

			$document_srl = $obj->document_srl;
			$comment_srl = $obj->comment_srl;

			$oCommentModel = &getModel('comment');
			$oComment = $oCommentModel->getComment($comment_srl,false,false);

			$oldComment = $oComment->variables;
			$logged_info = Context::get('logged_info');

			//관리자가 아닐경우 HackTag 제거
			if($logged_info->is_admin != 'Y')
			{
				$args->content = removeHackTag($obj->content);
			}

			if(!isset($obj->notify_message))
			{
				$args->notify_message = 'N';
			}

			if(!isset($obj->is_secret))
			{
				$args->is_secret = 'N';
			}

			if(!isset($obj->nick_name))
			{
				$args->nick_name = $logged_info->nick_name;
			}

			// 홈페이지 주소
			if(!isset($obj->homepage))
			{
				$args->homepage = '';
			}
			elseif(!preg_match('/^[a-z]+:\/\//i', $obj->homepage))
			{
				$args->homepage = "http://{$obj->homepage}";
			}

			/*
			 * 이전 저장된 comment의 내용과 비교한다.
			*/
			foreach($oldComment as $key => $val)
			{
				// 비교 대상이 되는 컬럼 값
				if(!in_array($key, array('is_secret','content','notify_message','password','email_address','homepage','ipaddress','nick_name')))
				{
					continue;
				}

				// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 해당 컬럼을 배열에 담는다.
				if(isset($obj->{$key}) && $val != $obj->{$key})
				{
					$is_diff->{$key} = TRUE;
				}
			}

			// 비교한 컬럼 정보와 함께 xe_comments의 글을 기록한다.
			if(count($is_diff) >= 1)
			{
				// type의 글 수정에 대한 수행번호를 선언
				$args->type = $this->memorize_type['comment'];
				// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
				$args->idx = $oMemorizeModel->getMemorizeLastIdx($comment_srl);
				$args->module_srl = $module_srl;
				$args->content_srl = $comment_srl;
				$args->content2 = $oldComment['content'];

				// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
				unset($oldComment['content']);
				$args->extra_vars = serialize($oldComment);

				// 수정시 기존에 등록되었던 글을 기록 합니다.
				$oMemorizeDatas = $this->insertMemorizeDatas($args);
				$memory_srl = $oMemorizeDatas->variables['memory_srl'];

				// 기록된 글의 sequence번호를 기록
				$args->memory_srl = $memory_srl;
				// 로그 기록 형식을 수정사항으로 기록
				$args->code = $this->memorize_code['update'];
				// 비교 결과 값이 다른 컬럼을 정리한다.
				$args->diff_column = serialize($is_diff);

				// 수정시 로그를 기록합니다.
				$this->insertMemorizeLog($args);
			}
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
			if(version_compare(__XE_VERSION__, '1.5.0', '>=')) 
			{
				return array('content', 'title', 'allow_trackback', 'status', 'is_notice', 'commentStatus', 'ipaddress', 'category_srl', 'homepage', 'notify_message', 'password');
			}
			return array('lang_code','is_notice','title','content','password','email_address','homepage','tags','extra_vars','ipaddress','allow_trackback','nofity_message','is_secret','allow_comment','lock_comment','nick_name');
		}

	}
/* End of file memorize.controller.php */
/* Location: ./modules/memorize/memorize.controller.php */