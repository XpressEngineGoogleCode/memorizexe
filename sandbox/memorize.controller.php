<?php
	/**
	 * @class  memorizeController
	 * @author 라르게덴, 카르마
	 * @brief Controller class of the module memorize
	 **/

	class memorizeController extends memorize
	{
		// Page 모듈로 수행되는 함수는 사용여부를 이 값으로 이용함
		var $memorize_config = FALSE;

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
			$is_memorize_diff = NULL;

			// 기록할 확장변수의 정보를 담는다.
			$memorize_diff = NULL;

			// 넘어온 값을 우선 배열에 담는다.
			$document_obj = $obj;

			// 모듈 설정을 가져옵니다.
			if(!$this->memorize_config && $obj->module != 'page')
			{
				$memorizeConfig = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
				if($memorizeConfig->use_update_document != 'Y')
				{
					return;
				}
			}
			else
			{
				// Page 모듈 설정을 가져옵니다.
				$config_page = $oMemorizeModel->getMemorizeConfig('page', 0);
				if($config_page->use_update_page != 'Y')
				{
					return;
				}
				// 사용여부 설정
				$this->memorize_config = TRUE;
			}

			// 아이피
			if(!isset($obj->ipaddress))
			{	// 현재 document.model.php updateDocument에 버그 같아서 이슈에 제출상태
				//$args->ipaddress = $_SERVER['REMOTE_ADDR'];
			}

			// 카테고리
			if(!isset($obj->category_srl))
			{
				$document_obj->category_srl = 0;
			}

			// 댓글등록여부
			if(isset($obj->comment_status))
			{
				$document_obj->commentStatus = $obj->comment_status;
			}
			else
			{
				$document_obj->commentStatus = 'DENY';
			}

			// 트랙백
			if($obj->allow_trackback != 'Y')
			{
				$document_obj->allow_trackback = 'N';
			}

			// 홈페이지 주소
			if(!isset($obj->homepage))
			{
				$document_obj->homepage = '';
			}
			elseif(!preg_match('/^[a-z]+:\/\//i', $obj->homepage))
			{
				$document_obj->homepage = "http://{$obj->homepage}";
			}

			// 알림
			if($obj->notify_message != 'Y')
			{
				$document_obj->notify_message = 'N';
			}

			// 패스워드
			if(isset($obj->password))
			{
				$document_obj->password = md5($obj->password);
			}

			// 제목이 없는 글은 본문의 내용으로 대체한다.
			if(isset($obj->title))
			{
				if($obj->title == '') $document_obj->title = cut_str(strip_tags($obj->content), 20, '...');
				if($obj->title == '') $document_obj->title = 'Untitled';
			}

			// 특정 문구를 제거
			$document_obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

			// 관리자 그룹이 아닐경우 본문에 HackTag를 제거
			if(Context::get('is_logged'))
			{
				$logged_info = Context::get('logged_info');

				if($logged_info->is_admin != 'Y')
				{
					$document_obj->content = removeHackTag($document_obj->content);
				}

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
					$is_memorize_diff->{$key} = TRUE;
				}
			}

			// 기존에 등록된 언어와 현재 설정된 언어가 다르다면 확장변수에서 찾는다
			if($is_lang)
			{
				// 확장변수 중 언어별 타이틀(-1)과 본문(-2)을 가져온다.
				$document_lang_obj->document_srl = $document_srl;
				$document_lang_obj->lang_code = Context::getLangType();
				$document_lang_obj->var_idx = array(-1, -2);
				$document_lang_output = $oMemorizeModel->getMemorizeWithDocumentExtraVars($document_lang_obj);
				unset($document_lang_obj);

				/*
				 * xe_document_extra_vars에서 언어별 타이틀, 본문을 비교한다.
				*/
				foreach($document_lang_output as $val)
				{
					// 언어별 타이틀을 비교하여 결과가 다르다면 해당 컬럼을 배열에 담고 해당 내용을 기록
					if($val->var_idx == -1 && $val->value != $obj->title)
					{
						$is_memorize_diff->extra_var_title = TRUE;

						// type의 글 수정에 대한 수행번호를 선언(언어별 확장변수)
						$document_lang_obj->type = $this->memorize_type['lang'];
						$document_lang_obj->content1 = $val->value;
						$document_lang_obj->content2 = $val->var_idx;
						// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 컬럼은 제거 합니다.
						unset($val->value);
						$document_lang_obj->extra_vars = serialize($val);

						$memorize_diff->extra_var_title = $document_lang_obj;
						unset($document_lang_obj);
					}
					// 언어별 본문을 비교하여 결과가 다르다면 해당 컬럼을 배열에 담고 해당 내용을 기록
					elseif($val->var_idx == -2 && $val->value != $obj->content)
					{
						$is_memorize_diff->extra_var_content = TRUE;

						// type의 글 수정에 대한 수행번호를 선언
						$document_lang_obj->type = $this->memorize_type['lang'];
						$document_lang_obj->content1 = $val->value;
						$document_lang_obj->content2 = $val->var_idx;
						// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 컬럼은 제거 합니다.
						unset($val->value);
						$document_lang_obj->extra_vars = serialize($val);

						$memorize_diff->extra_var_content = $document_lang_obj;
						unset($document_lang_obj);
					}
				}
			}

			// 확장변수 중 언어별 데이터를 가져온다.
			$document_extra_var_obj->document_srl = $document_srl;
			$document_extra_var_obj->lang_code = Context::getLangType();
			$document_extra_var_obj->not_var_idx = array(-1, -2);
			$document_extra_var_output = $oMemorizeModel->getMemorizeWithDocumentExtraVars($document_extra_var_obj);
			unset($document_extra_var_obj);

			/*
			 * xe_document_extra_vars에서 언어별 타이틀, 본문을 제외한 확장변수를 비교한다.
			*/
			foreach($document_extra_var_output as $val)
			{
				// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 해당 컬럼을 배열에 담는다.
				if(isset($obj->{"extra_vars{$val->var_idx}"}) && $val->value != $obj->{"extra_vars{$val->var_idx}"})
				{
					$is_memorize_diff->{"extra_vars{$val->var_idx}"} = TRUE;

					// type의 글 수정에 대한 수행번호를 선언
					$document_extra_var_obj->type = $this->memorize_type['extra_var'];
					$document_extra_var_obj->content1 = $val->value;
					$document_extra_var_obj->content2 = $val->var_idx;
					// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 컬럼은 제거 합니다.
					unset($val->value);
					$document_extra_var_obj->extra_vars = serialize($val);

					$memorize_diff->{"extra_vars{$val->var_idx}"} = $document_extra_var_obj;
					unset($document_extra_var_obj);
				}
			}

			// 비교한 컬럼 정보와 함께 xe_documents의 글을 기록한다.
			if(count($is_memorize_diff) >= 1)
			{
				// type의 글 수정에 대한 수행번호를 선언
				$memorizeData_obj->type = $this->memorize_type['document'];
				// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.

				$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($document_srl);
				$memorizeData_obj->module_srl = $module_srl;
				$memorizeData_obj->content_srl = $document_srl;
				$memorizeData_obj->parent_srl = $module_srl;
				$memorizeData_obj->content1 = $oldDocument['title'];
				$memorizeData_obj->content2 = $oldDocument['content'];
				// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
				unset($oldDocument['content']);
				$memorizeData_obj->extra_vars = serialize($oldDocument);

				// 수정시 기존에 등록되었던 글을 기록 합니다.
				$memorizeData_output = $this->insertMemorizeDatas($memorizeData_obj);
				$memory_srl = $memorizeData_output->variables['memory_srl'];

				// 확장변수 정보를 기록(언어별 글, 확장변수)
				if(count($memorize_diff) >= 1)
				{
					foreach($memorize_diff as $val)
					{
						$memorizeData_obj = $val;
						$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($document_srl);
						$memorizeData_obj->module_srl = $module_srl;
						$memorizeData_obj->content_srl = $document_srl;
						// 부모를 글 정보의 memory_srl로 지정
						$memorizeData_obj->parent_srl = $memory_srl;
						$this->insertMemorizeDatas($memorizeData_obj);
					}
				}

				// 로그 기록에 필요한 정보
				$memorizeLog_obj->module_srl = $module_srl;
				$memorizeLog_obj->content_srl = $document_srl;
				// 기록된 글의 sequence번호를 기록
				$memorizeLog_obj->memory_srl = $memory_srl;
				// 로그 기록 형식을 수정사항으로 기록
				$memorizeLog_obj->code = $this->memorize_code['update'];
				// 비교 결과 값이 다른 컬럼을 정리한다.
				$memorizeLog_obj->diff_column = serialize($is_memorize_diff);

				// 수정시 로그를 기록합니다.
				$this->insertMemorizeLog($memorizeLog_obj);
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
			if(!$obj->document_srl)
			{
				return;
			}

			//삭제때는 달랑 이거 하나 넘어오넹~~
			$document_srl = $obj->document_srl;

			//문서번호에서 module_info 가져오기
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
			$module = $module_info->module;
			$module_srl = $module_info->module_srl;

			$oDocumentModel = &getModel('document');
			$oMemorizeModel = &getModel('memorize');

			// Page 모듈 설정여부가 미사용일 경우 수행
			if(!$this->memorize_config)
			{
				// 모듈 설정을 가져옵니다.
				$memorizeConfig = $oMemorizeModel->getMemorizeConfig($module, $module_srl);

				if($memorizeConfig->block_del_document == 'Y')
				{
					$oCommentModel = &getModel('comment');
					$child = $oCommentModel->getCommentCount($document_srl);
					if($child > 0)
					{
						return new Object(-1, 'block_del_document');
					}
				}

				if($memorizeConfig->use_delete_document != 'Y')
				{
					return;
				}
			}

			$oDocument = $oDocumentModel->getDocument($document_srl, FALSE, FALSE);
			$oldDocument = $oDocument->variables;

			//저장할 변수처리
			$memorizeData_obj->type = $this->memorize_type['document'];
			$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($document_srl);
			$memorizeData_obj->module_srl = $module_srl;
			$memorizeData_obj->content_srl = $document_srl;
			$memorizeData_obj->parent_srl = $module_srl;
			$memorizeData_obj->content1 = $oldDocument['title'];
			$memorizeData_obj->content2 = $oldDocument['content'];

			// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
			unset($oldDocument['content']);
			$memorizeData_obj->extra_vars = serialize($oldDocument);

			$memorizeData_output = $this->insertMemorizeDatas($memorizeData_obj);
			$memory_srl = $memorizeData_output->variables['memory_srl'];

			/*
			 * extra_lang
			*/
			// 확장변수 중 언어별 타이틀(-1)과 본문(-2)을 가져온다.
			$document_lang_obj->document_srl = $document_srl;
			$document_lang_obj->var_idx = array(-1, -2);
			if($document_lang_output = $oMemorizeModel->getMemorizeWithDocumentExtraVars($document_lang_obj))
			{
				/*
				 * xe_document_extra_vars 언어별
				*/
				foreach($document_lang_output as $val)
				{
					//저장할 변수처리
					$memorizeData_obj->type = $this->memorize_type['lang'];
					$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($document_srl);
					$memorizeData_obj->module_srl = $module_srl;
					$memorizeData_obj->content_srl = $document_srl;
					$memorizeData_obj->parent_srl = $memory_srl;
					$memorizeData_obj->content1 = $val->value;
					$memorizeData_obj->content2 = $val->var_idx;

					// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
					unset($val->value);
					$memorizeData_obj->extra_vars = serialize($val);

					$this->insertMemorizeDatas($memorizeData_obj);
				}
			}

			/*
			 * extra_var
			*/
			// 확장변수 중 언어별 타이틀(-1)과 본문(-2)을 가져온다.
			$document_extra_var_obj->document_srl = $document_srl;
			$document_extra_var_obj->not_var_idx = array(-1, -2);
			if($document_extra_var_output = $oMemorizeModel->getMemorizeWithDocumentExtraVars($document_extra_var_obj))
			{
				/*
				 * xe_document_extra_vars
				*/
				foreach($document_extra_var_output as $val)
				{
					//저장할 변수처리
					$memorizeData_obj->type = $this->memorize_type['extra_var'];
					$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($document_srl);
					$memorizeData_obj->module_srl = $module_srl;
					$memorizeData_obj->content_srl = $document_srl;
					$memorizeData_obj->parent_srl = $memory_srl;
					$memorizeData_obj->content1 = $val->value;
					$memorizeData_obj->content2 = $val->var_idx;

					// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
					unset($val->value);
					$memorizeData_obj->extra_vars = serialize($val);

					$this->insertMemorizeDatas($memorizeData_obj);
				}
			}

			// 로그 기록에 필요한 정보
			$memorizeLog_obj->module_srl = $module_srl;
			$memorizeLog_obj->content_srl = $document_srl;
			// 기록된 글의 sequence번호를 기록
			$memorizeLog_obj->memory_srl = $memory_srl;
			// 로그 기록 형식을 수정사항으로 기록
			$memorizeLog_obj->code = $this->memorize_code['delete'];

			// 수정시 로그를 기록합니다.
			$this->insertMemorizeLog($memorizeLog_obj);

			/*
			 * xe_files
			*/
			$oFileModel = &getModel('file');
			if($file_output = $oFileModel->getFiles($document_srl))
			{
				foreach($file_output as $val)
				{
					$this->triggerDeleteFile($val);
				}
			}
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

			$oModuleModel = &getModel('module');
			$oMemorizeModel = &getModel('memorize');

			//module_srl에서 module_info 가져오기
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			$module = $module_info->module;

			// 비교결과를 담는다.
			$is_diff = NULL;

			// 넘어온 값을 우선 배열에 담는다.
			$comment_obj = $obj;

			// Page 모듈 설정여부가 미사용일 경우 수행
			if(!$this->memorize_config)
			{
				// 모듈 설정을 가져옵니다.
				$memorize_config = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
				if($memorize_config->use_update_comment != 'Y')
				{
					return;
				}
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
				$comment_obj->content = removeHackTag($obj->content);
			}

			if(!isset($obj->notify_message))
			{
				$comment_obj->notify_message = 'N';
			}

			if(!isset($obj->is_secret))
			{
				$comment_obj->is_secret = 'N';
			}

			if(!isset($obj->nick_name))
			{
				$comment_obj->nick_name = $logged_info->nick_name;
			}

			// 홈페이지 주소
			if(!isset($obj->homepage))
			{
				$comment_obj->homepage = '';
			}
			elseif(!preg_match('/^[a-z]+:\/\//i', $obj->homepage))
			{
				$comment_obj->homepage = "http://{$obj->homepage}";
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
				$memorizeData_obj->type = $this->memorize_type['comment'];
				// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
				$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($comment_srl);
				$memorizeData_obj->module_srl = $module_srl;
				$memorizeData_obj->content_srl = $comment_srl;
				$memorizeData_obj->content2 = $oldComment['content'];

				// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
				unset($oldComment['content']);
				$memorizeData_obj->extra_vars = serialize($oldComment);

				// 수정시 기존에 등록되었던 글을 기록 합니다.
				$oMemorizeDatas = $this->insertMemorizeDatas($memorizeData_obj);
				$memory_srl = $oMemorizeDatas->variables['memory_srl'];

				// 로그 기록에 필요한 정보
				$memorizeLog_obj->module_srl = $module_srl;
				$memorizeLog_obj->content_srl = $val->comment_srl;
				// 기록된 글의 sequence번호를 기록
				$memorizeLog_obj->memory_srl = $memory_srl;
				// 로그 기록 형식을 수정사항으로 기록
				$memorizeLog_obj->code = $this->memorize_code['update'];
				// 비교 결과 값이 다른 컬럼을 정리한다.
				$memorizeLog_obj->diff_column = serialize($is_diff);

				// 수정시 로그를 기록합니다.
				$this->insertMemorizeLog($memorizeLog_obj);
			}
		}

		/**
		 * @brief 댓글 삭제
		 **/
		function triggerDeleteComment(&$obj)
		{
			if(!$obj->module_srl || !$obj->document_srl || !$obj->comment_srl)
			{
				return;
			}

			$module_srl = $obj->module_srl;
			$comment_srl = $obj->comment_srl;

			$oModuleModel = &getModel('module');
			$oCommentModel = &getModel('comment');
			$oMemorizeModel = &getModel('memorize');

			//module_srl에서 module_info 가져오기
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			$module = $module_info->module;

			// Page 모듈 설정여부가 미사용일 경우 수행
			if(!$this->memorize_config)
			{
				// 모듈 설정을 가져옵니다.
				$memorize_config = $oMemorizeModel->getMemorizeConfig($module, $module_srl);
				if($memorize_config->use_delete_comment != 'Y')
				{
					return;
				}
			}

			if(!$obj->variables)
			{
				$comment = $oCommentModel->getComment($obj->comment_srl);
				$obj->variables = $comment->variables;
			}

			$oldComment = $obj->variables;

			// Depth 정보를 가져와서 추가합니다.
			$comment_list_output = $oMemorizeModel->getCommentListItem($obj);
			$oldComment['head'] = $comment_list_output->head;
			$oldComment['arrange'] = $comment_list_output->arrange;
			$oldComment['depth'] = $comment_list_output->depth;

			//저장할 변수처리
			$memorizeData_obj->type = $this->memorize_type['comment'];

			// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
			$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($comment_srl);
			$memorizeData_obj->module_srl = $module_srl;
			$memorizeData_obj->content_srl = $comment_srl;

			if($oldComment['parent_srl'] == 0)
			{
				$oldComment['parent_srl'] = $oldComment['document_srl'];
			}
			$memorizeData_obj->parent_srl = $oldComment['parent_srl'];
			$memorizeData_obj->content2 = $oldComment['content'];

			// extra_vars는 데이터 타입이 text이기 때문에 bigtext 타입의 본문은 제거 합니다.
			unset($oldComment['content']);
			$memorizeData_obj->extra_vars = serialize($oldComment);

			$oMemorizeDatas = $this->insertMemorizeDatas($memorizeData_obj);
			$memory_srl = $oMemorizeDatas->variables['memory_srl'];

			// 로그 기록에 필요한 정보
			$memorizeLog_obj->module_srl = $module_srl;
			$memorizeLog_obj->content_srl = $comment_srl;
			// 기록된 글의 sequence번호를 기록
			$memorizeLog_obj->memory_srl = $memory_srl;
			// 로그 기록 형식을 수정사항으로 기록
			$memorizeLog_obj->code = $this->memorize_code['delete'];

			// 수정시 로그를 기록합니다.
			$this->insertMemorizeLog($memorizeLog_obj);

			// 파일삭제
			$oFileModel = &getModel('file');
			if($file_output = $oFileModel->getFiles($comment_srl))
			{
				foreach($file_output as $val)
				{
					$this->triggerDeleteFile($val);
				}
			}
		}

		/**
		 * @brief 첨부파일 삭제
		 **/
		function triggerDeleteFile(&$obj)
		{
			if(!$obj->module_srl || !$obj->upload_target_srl || !$obj->file_srl)
			{
				return;
			}

			// valid가 아니면 패쓰...
			if($obj->isvalid != 'Y') return;

			$module_srl = $obj->module_srl;
			$upload_target_srl = $obj->upload_target_srl;
			$file_srl = $obj->file_srl;

			// module_srl에서 module_info 가져오기
			$oModuleModel = &getModel('module');
			$oMemorizeModel = &getModel('memorize');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			// Page 모듈 설정여부가 미사용일 경우 수행
			if(!$this->memorize_config)
			{
				// 모듈 설정을 가져옵니다.
				$memorize_config = $oMemorizeModel->getMemorizeConfig($module_info->module, $module_srl);
				if($memorize_config->use_delete_file != 'Y')
				{
					return;
				}
			}

			// 파일이 있으면 백업 수행
			$file = realpath($obj->uploaded_filename);
			if(file_exists($file))
			{
				if($obj->direct_download == 'Y')
				{
					$path = sprintf('./files/memoriz_attach/images/%s/%s', $module_srl, getNumberingPath($upload_target_srl, 3));
				}
				else
				{
					$path = sprintf('./files/memoriz_attach/binaries/%s/%s', $module_srl, getNumberingPath($upload_target_srl, 3));
				}

				// 폴더생성
				if(FileHandler::makeDir($path))
				{
					// 원래이름을 보전하자...
					$file_fullname = substr(strrchr($obj->uploaded_filename, '/'), 1);

					// 보관할 화일
					$memorize_file = sprintf('%s%s', $path, $file_fullname);

					// 이미 같은 이름의 파일이 있다면 파일명 끝에 -1를 붙임
					if(file_exists($memorize_file))
					{
						$file_ext = substr(strrchr($file_fullname, '.'), 1);
						$file_name = preg_replace('/{$file_ext}$/', '', $file_name);
						$file_fullname = "{$file_name}-1{$file_ext}";
						$memorize_file = sprintf('%s%s', $path, $file_fullname);
					}

					// 복사
					FileHandler::copyFile($file, $memorize_file);
				}
			}

			// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
			$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($obj->file_srl);
			$memorizeData_obj->content1 = $obj->source_filename;
			$memorizeData_obj->content2 = $memorize_file;
			$memorizeData_obj->extra_vars = serialize($obj);
			$memorizeData_obj->module_srl = $module_srl;
			$memorizeData_obj->content_srl = $obj->file_srl;
			$memorizeData_obj->parent_srl = $obj->upload_target_srl;
			$memorizeData_obj->type = $this->memorize_type['file'];

			$oMemorizeDatas = $this->insertMemorizeDatas($memorizeData_obj);
			$memory_srl = $oMemorizeDatas->variables['memory_srl'];

			// 로그 기록에 필요한 정보
			$memorizeLog_obj->module_srl = $module_srl;
			$memorizeLog_obj->content_srl = $file_srl;
			// 기록된 글의 sequence번호를 기록
			$memorizeLog_obj->memory_srl = $memory_srl;
			// 로그 기록 형식을 수정사항으로 기록
			$memorizeLog_obj->code = $this->memorize_code['delete'];

			// 수정시 로그를 기록합니다.
			$this->insertMemorizeLog($memorizeLog_obj);
		}

		/**
		 * @brief 출력부에서 스크립트 호출
		 **/
		function triggerDisplay(&$obj)
		{
			// URL에서 act값을 구해 Memorize 트리거 처리가 필요한 act에 자바스크립트 수행
			if(in_array(Context::get('act'), 
				array(
					'dispLayoutAdminEdit', 
					'dispLayoutAdminInstanceList', 
					'dispPageAdminContentModify',
					'dispPageAdminDelete',
				)))
			{
				Context::addJsFile('./modules/memorize/tpl/js/memorize.js', NULL, NULL, NULL, 'body');
			}
		}

		/**
		 * @brief 레이아웃 편집
		 **/
		function triggerUpdateCodeLayout()
		{
			$layout_obj = Context::gets('layout_srl', 'code', 'code_css');
			if(!$layout_srl = $layout_obj->layout_srl)
			{
				return;
			}

			// 모듈 설정을 가져옵니다.
			$oMemorizeModel = &getModel('memorize');
			$config_layuout = $oMemorizeModel->getMemorizeConfig('layout', 0);
			if($config_layuout->use_update_layout != 'Y')
			{
				return;
			}

			$layout_obj->code = preg_replace('/]]&gt;/', ']]>', $layout_obj->code); //<![CDATA[ //]]> 라는 구문 중 ]]> 문자열을 exec_xml로 넘기면 문제가 발생됨
			$layout_obj->code = preg_replace('/<\?.*(\?>)?/Usm', '', $layout_obj->code);
			$layout_obj->code = preg_replace('/<script[\s]*language[\s]*=("|\')php("|\')[\s]*>.*<\/script>/Usm', '', $layout_obj->code);

			// HTML 코드를 Memorize용 TEMP파일에서 가져옴
			$oLayoutModel = &getModel('layout');
			$layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
			$layout_buff = FileHandler::readFile($layout_file.'.memorize');

			// CSS 코드를 Memorize용 TEMP파일에서 가져옴
			$layout_css_file = $oLayoutModel->getUserLayoutCss($layout_srl);
			$layout_css_buff = FileHandler::readFile($layout_css_file.'.memorize');

			/*
			 * 이전 저장된 layout의 code 내용과 비교한다.
			*/
			// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 해당 컬럼을 배열에 담는다.
			if(isset($layout_buff) && $layout_buff != $layout_obj->code)
			{
				$is_diff->code = TRUE;
			}

			// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 해당 컬럼을 배열에 담는다.
			if(isset($layout_css_buff) && $layout_css_buff != $layout_obj->code_css)
			{
				$is_diff->code_css = TRUE;
			}

			// 비교값이 전과 후가 불일치 할 경우
			if(count($is_diff) >= 1)
			{
				// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
				$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($layout_srl);
				$memorizeData_obj->content1 = $layout_buff;
				$memorizeData_obj->content2 = $layout_css_buff;
				$memorizeData_obj->extra_vars = null;
				$memorizeData_obj->module_srl = $layout_srl;
				$memorizeData_obj->content_srl = $layout_srl;
				$memorizeData_obj->parent_srl = $layout_srl;
				$memorizeData_obj->type = $this->memorize_type['layout'];
	
				$oMemorizeDatas = $this->insertMemorizeDatas($memorizeData_obj);
				$memory_srl = $oMemorizeDatas->variables['memory_srl'];
	
				// 로그 기록에 필요한 정보
				$memorizeLog_obj->module_srl = $layout_srl;
				$memorizeLog_obj->content_srl = $layout_srl;
				// 기록된 글의 sequence번호를 기록
				$memorizeLog_obj->memory_srl = $memory_srl;
				// 로그 기록 형식을 수정사항으로 기록
				$memorizeLog_obj->code = $this->memorize_code['update'];
				// 비교 결과 값이 다른 컬럼을 정리한다.
				$memorizeLog_obj->diff_column = serialize($is_diff);

				// 수정시 로그를 기록합니다.
				$this->insertMemorizeLog($memorizeLog_obj);
			}

			/*
				기존 TEMP 파일이 POST로 넘겨받은 방식이라 내려쓰기의 구문이 통상구문과 다름니다.(파일 유형, 내려쓰기 기호 구문이 다름)
				때문에 JAF 트리거로 받아온 값과는 비교일치가 안되기 때문에 JAF 트리거로 넘어오는 비교용 코드를 Memorize용으로 저장시킵니다.
			*/
			FileHandler::writeFile($layout_file.'.memorize', $layout_obj->code);
			FileHandler::writeFile($layout_css_file.'.memorize', $layout_obj->code_css);
		}

		/**
		 * @brief 레이아웃 삭제
		 **/
		function triggerLayoutDelete()
		{
			if(!$layout_srl = Context::get('layout_srl'))
			{
				return;
			}

			// 모듈 설정을 가져옵니다.
			$oMemorizeModel = &getModel('memorize');
			$config_layuout = $oMemorizeModel->getMemorizeConfig('layout', 0);
			if($config_layuout->use_delete_layout != 'Y')
			{
				return;
			}

			// Get layout information
			$oLayoutModel = &getModel('layout');
			$layout_info = $oLayoutModel->getLayout($layout_srl);

			// HTML 코드를 Memorize용 TEMP파일에서 가져옴
			$layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
			$layout_buff = FileHandler::readFile($layout_file);

			// CSS 코드를 Memorize용 TEMP파일에서 가져옴
			$layout_css_file = $oLayoutModel->getUserLayoutCss($layout_srl);
			$layout_css_buff = FileHandler::readFile($layout_css_file);

			// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
			$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($layout_srl);
			$memorizeData_obj->content1 = $layout_buff;
			$memorizeData_obj->content2 = $layout_css_buff;
			$memorizeData_obj->extra_vars = serialize($layout_info);
			$memorizeData_obj->module_srl = $layout_srl;
			$memorizeData_obj->content_srl = $layout_srl;
			$memorizeData_obj->parent_srl = $layout_srl;
			$memorizeData_obj->type = $this->memorize_type['layout'];

			$oMemorizeDatas = $this->insertMemorizeDatas($memorizeData_obj);
			$memory_srl = $oMemorizeDatas->variables['memory_srl'];

			// 로그 기록에 필요한 정보
			$memorizeLog_obj->module_srl = $layout_srl;
			$memorizeLog_obj->content_srl = $layout_srl;
			// 기록된 글의 sequence번호를 기록
			$memorizeLog_obj->memory_srl = $memory_srl;
			// 로그 기록 형식을 수정사항으로 기록
			$memorizeLog_obj->code = $this->memorize_code['delete'];

			// 수정시 로그를 기록합니다.
			$this->insertMemorizeLog($memorizeLog_obj);
		}

		/**
		 * @brief 페이지 편집
		 **/
		function triggerPageInsertContent()
		{
			$page_obj = Context::gets('mid', 'module_srl', 'document_srl', 'content');
			if(!$module_srl = $page_obj->module_srl)
			{
				return;
			}

			// 모듈 설정을 가져옵니다.
			$oMemorizeModel = &getModel('memorize');
			$config_page = $oMemorizeModel->getMemorizeConfig('page', 0);
			if($config_page->use_update_page != 'Y')
			{
				return;
			}
			// Page 모듈수행 변수선언
			$this->memorize_config = TRUE;

			// 모듈 정보
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			/*
			 * 이전 저장된 page의 code 내용과 비교한다.
			 * 단, 페이지 생성 타입이 WIDGET일 경우만 수행 (ARTICLE 타입일 경우 내부 트리거로 처리됨)
			*/
			// 비교하려는 변수가 존재하고, 비교 결과가 다르다면 해당 컬럼을 배열에 담는다.
			if(isset($module_info->content) && $module_info->content != $page_obj->content)
			{
				$content = $val->content;
				$is_diff->code = TRUE;
			}

			// 비교값이 전과 후가 불일치 할 경우
			if(count($is_diff) >= 1)
			{
				// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
				$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($module_srl);
				$memorizeData_obj->content1 = $module_info->content;
				$memorizeData_obj->content2 = null;
				$memorizeData_obj->extra_vars = serialize($module_info);
				$memorizeData_obj->module_srl = $module_srl;
				$memorizeData_obj->content_srl = $module_srl;
				$memorizeData_obj->parent_srl = $module_srl;
				$memorizeData_obj->type = $this->memorize_type['page'];

				$oMemorizeDatas = $this->insertMemorizeDatas($memorizeData_obj);
				$memory_srl = $oMemorizeDatas->variables['memory_srl'];

				// 로그 기록에 필요한 정보
				$memorizeLog_obj->module_srl = $module_srl;
				$memorizeLog_obj->content_srl = $module_srl;
				// 기록된 글의 sequence번호를 기록
				$memorizeLog_obj->memory_srl = $memory_srl;
				// 로그 기록 형식을 수정사항으로 기록
				$memorizeLog_obj->code = $this->memorize_code['update'];
				// 비교 결과 값이 다른 컬럼을 정리한다.
				$memorizeLog_obj->diff_column = serialize($is_diff);

				// 수정시 로그를 기록합니다.
				$this->insertMemorizeLog($memorizeLog_obj);
			}
		}

		/**
		 * @brief 페이지 삭제
		 **/
		function triggerPageDelete()
		{
			if(!$module_srl = Context::get('module_srl'))
			{
				return;
			}

			// 모듈 설정을 가져옵니다.
			$oMemorizeModel = &getModel('memorize');
			$config_page = $oMemorizeModel->getMemorizeConfig('page', 0);
			if($config_page->use_delete_page != 'Y')
			{
				return;
			}
			// Page 모듈수행 변수선언
			$this->memorize_config = TRUE;

			// 모듈 정보
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			// 페이지 타입이 문서형일 경우
			if($module_info->page_type == 'ARTICLE')
			{
				// 모듈번호에 해당하는 문서정보를 가져와 문서삭제 트리거를 수행
				$document_args->module_srl = $module_srl;
				$document_list = executeQuery('memorize.getDocumentList', $document_args);
				$document_list->data->module = $module_info->module;
				$this->triggerDeleteDocument($document_list->data);
			}

			// 마지막 글의 idx를 가져와서 양수로 바꾼 후 1을 더한 다음, 다시 음수로 바꿉니다.
			$memorizeData_obj->idx = $oMemorizeModel->getMemorizeLastIdx($module_srl);
			$memorizeData_obj->content1 = $module_info->content;
			$memorizeData_obj->content2 = null;
			$memorizeData_obj->extra_vars = serialize($module_info);
			$memorizeData_obj->module_srl = $module_srl;
			$memorizeData_obj->content_srl = $module_srl;
			$memorizeData_obj->parent_srl = $module_srl;
			$memorizeData_obj->type = $this->memorize_type['page'];

			$oMemorizeDatas = $this->insertMemorizeDatas($memorizeData_obj);
			$memory_srl = $oMemorizeDatas->variables['memory_srl'];

			// 로그 기록에 필요한 정보
			$memorizeLog_obj->module_srl = $module_srl;
			$memorizeLog_obj->content_srl = $module_srl;
			// 기록된 글의 sequence번호를 기록
			$memorizeLog_obj->memory_srl = $memory_srl;
			// 로그 기록 형식을 수정사항으로 기록
			$memorizeLog_obj->code = $this->memorize_code['delete'];

			// 수정시 로그를 기록합니다.
			$this->insertMemorizeLog($memorizeLog_obj);
		}

		/**
		 * @brief 버전에 따른 비교컬럼 처리
		 **/
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