/**
 * @file   common/js/xe.min.js
 * @author 라르게덴, 카르마
 * @brief  트리거 스크립트
 **/

/* JAF선언 */
var memorize = xe.createApp('memorizeJAF',
	{
		// 초기 수행
		init : function()
		{
			// URL에서 act값을 구합니다.
			var url_split = current_url.split('&');
			var memorize_api;
			jQuery.each(url_split, function(key, val)
			{
				switch(val)
				{
					// 레이아웃 편집
					case 'act=dispLayoutAdminEdit' : 
						memorize_api = 'LAYOUT_CODE_UPDATE_INIT';
						break;
					// 생성된 레이아웃(레이아웃 모듈 제거 페이지)
					case 'act=dispLayoutAdminInstanceList' : 
						memorize_api = 'LAYOUT_DELETE_INIT';
						break;
					// 페이지 편집
					case 'act=dispPageAdminContentModify' : 
						memorize_api = 'PAGE_INSERT_CONTENT_INIT';
						break;
					// 페이지 모듈 제거
					case 'act=dispPageAdminDelete' : 
						memorize_api = 'PAGE_DELETE_INIT';
						break;
					default: break;
				}
			});

			// JAF용 API 호출() (하단에 있는 API_값 으로 시작하는것)
			this.cast(memorize_api);
		},
		// 레이아웃 편집
		API_LAYOUT_CODE_UPDATE_INIT : function()
		{
			var params = new Array();
			var form;

			// Form 수행이 되는 순간 일단 수행을 정지시키고 트리거 진행
			jQuery('#fo_layout').live('submit',function(event)
			{
				event.preventDefault(); // <- 수행 정지
				form = this;
				params['layout_srl'] = jQuery('input:hidden[name=layout_srl]', this).val();
				params['code'] = jQuery('textarea:[name=code]', this).val();
				params['code'] = params['code'].replace(/]]>/g, ']]&gt;'); //<![CDATA[ //]]> 라는 구문 중 ]]> 문자열을 exec_xml로 넘기면 문제가 발생됨
				params['code_css'] = jQuery('textarea:[name=code_css]', this).val();

				// 트리거 호출
				exec_xml('memorize', 'triggerUpdateCodeLayout', params, function()
				{
					// 완료시 Form 처리 수행
					oMemorize.cast('SUBMIT', [form]);
				});
			});

		},
		// 레이아웃 모듈 제거
		API_LAYOUT_DELETE_INIT : function()
		{
			var params = new Array();
			var form;

			// Form 수행이 되는 순간 일단 수행을 정지시키고 트리거 진행
			jQuery('.layout_delete_form').live('submit',function(event)
			{
				event.preventDefault(); // <- 수행 정지
				form = this;
				params['layout_srl'] = jQuery('input:hidden[name=layout_srl]', this).val();

				// 트리거 호출
				exec_xml('memorize', 'triggerLayoutDelete', params, function()
				{
					// 완료시 Form 처리 수행
					oMemorize.cast('SUBMIT', [form]);
				});
			});
		},
		// 페이지 제거
		API_PAGE_DELETE_INIT : function()
		{
			var params = new Array();
			var form;

			// Form 에 ID 생성
			jQuery('form input:hidden[value=procPageAdminDelete]').parent().attr('id', 'memorize_page');

			// Form 수행이 되는 순간 일단 수행을 정지시키고 트리거 진행
			jQuery('#memorize_page').live('submit',function(event)
			{
				event.preventDefault(); // <- 수행 정지
				form = this;
				params['module_srl'] = jQuery('input:hidden[name=module_srl]', this).val();
				
				// 트리거 호출
				exec_xml('memorize', 'triggerPageDelete', params, function()
				{
					// 완료시 Form 처리 수행
					oMemorize.cast('SUBMIT', [form]);
				});
			});
		},
		// 페이지 편집
		API_PAGE_INSERT_CONTENT_INIT : function()
		{
			// Form 처리에 사용된 함수선언을 Memorize 트리거용 함수로 교체
			jQuery('#pageFo').
			attr('onsubmit', 'oMemorize.procPageInsertContent(this); return false;');
		},
		// 편집한 페이지 컨텐츠를 저장
		procPageInsertContent : function(fo_obj)
		{
			var html = getWidgetContent();
			fo_obj.content.value = html;
			procFilter(fo_obj, insert_page_content);
		},
		API_COMPLETE : function(sender, params)
		{
			jQuery(params[0]).submit();
		},
		API_SUBMIT : function(sender, params)
		{
			params[0].submit();
		}
	});

/*
 * @file   common/js/xml_js_filter.js
 * @author NHN (developers@xpressengine.com)
 * @brief  페이지 편집시 Form 처리 수행이 되지 않는 구조를 띄고 있어서 함수 재선언을 통해 트리거 기능을 추가함
*/
function legacy_filter(filter_name, form, module, act, callback, responses, confirm_msg, rename_params) {
	var v = xe.getApp('Validator')[0], $ = jQuery, args = [];

	if (!v) return false;

	if (!form.elements['_filter']) $(form).prepend('<input type="hidden" name="_filter" />');
	form.elements['_filter'].value = filter_name;

	args[0] = filter_name;
	args[1] = function(f) {
		var params = {}, res = [], elms = f.elements, data = $(f).serializeArray();
		$.each(data, function(i, field) {
			var v = $.trim(field.value), n = field.name;
			if(!v || !n) return true;
			if(rename_params[n]) n = rename_params[n];

			if(/\[\]$/.test(n)) n = n.replace(/\[\]$/, '');
			if(params[n]) params[n] += '|@|'+v;
			else params[n] = field.value;
		});

		if (confirm_msg && !confirm(confirm_msg)) return false;

		/* Memorize 트리거 (페이지 편집용) 시작 */
		if(act == 'procPageAdminInsertContent')
		{
			rename_params['mid'] = form.elements['mid'].value;
			rename_params['module_srl'] = form.elements['module_srl'].value;
			rename_params['document_srl'] = form.elements['document_srl'].value;
			rename_params['content'] = form.elements['content'].value;
			exec_xml('memorize', 'triggerPageInsertContent', rename_params);
		}
		/* Memorize 트리거 (페이지 편집용) 끝 */

		exec_xml(module, act, params, callback, responses, params, form);
	};

	v.cast('ADD_CALLBACK', args);
	v.cast('VALIDATE', [form, filter_name]);

	return false;
}

// App 객체의 인스턴스 생성
var oMemorize = new memorize;