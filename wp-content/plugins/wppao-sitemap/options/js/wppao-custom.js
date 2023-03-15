jQuery(document).ready(function($) {

    if($('#tanwxgzh').length){
        $('#tanwxgzh').click(function(){
            $('.wppao-pgs-gzh').toggle(1000);
        });
    }

    if($('.wm_group_switch').length){
        var wmSwitch = $('.wm_group_switch input:checked').val();
        if( wmSwitch == 'image' ){
            $('.wm_group_text').css('display','none');
            $('.wm_group_image').css('display','block');
        }else{
            $('.wm_group_text').css('display','block');
            $('.wm_group_image').css('display','none');
        }


        $('.wm_group_switch input').change(function(){
            var wmSwitch = $(this).val();
            if( wmSwitch == 'image' ){
                $('.wm_group_text').css('display','none');
                $('.wm_group_image').css('display','block');
            }
            else{
                $('.wm_group_text').css('display','block');
                $('.wm_group_image').css('display','none');
            }
        });
    }
});

