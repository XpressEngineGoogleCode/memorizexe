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
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() 
		{
            $oDB = &DB::getInstance();
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() 
		{
            $oDB = &DB::getInstance();
            return new Object(0, 'success_updated');
        }
		
		/**
         * @brief 모듈 삭제 실행
         **/
		function moduleUninstall() {
            return new Object();
        }


        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() 
		{
        }
    }
/* End of file memorize.class.php */
/* Location: ./modules/memorize/memorize.class.php */
