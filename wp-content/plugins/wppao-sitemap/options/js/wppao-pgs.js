(function($){
    // Uploader
    $('#wppao-pgs-form').on('click', '.upload-btn', function(e) {
        e.preventDefault();
        var uploader, id = $(this).attr('id');
        if (uploader) {
            uploader.open();
        }else{
            uploader = wp.media.frames.file_frame = wp.media({
                title: '选择文件',
                button: {
                    text: '选择文件'
                },
                multiple: false
            });
            uploader.on('select', function() {
                var attachment = uploader.state().get('selection').first().toJSON();
                var inputId = id.replace(/_upload/i,'');
                $('#'+inputId).val(attachment.url);
            });
            uploader.open();
        }
    }).on('click', '.toggle', function(){
        var $label = $(this);
        if($label.hasClass('active')){
            $label.removeClass('active');
            $label.next().val(0);
        }else{
            $label.addClass('active');
            $label.next().val(1);
        }
    }).on('change', '.toggle-wrap input', function(){
        var $this = $(this);
        if($this.val()==1){
            $this.parent().find('.toggle').addClass('active');
        }else{
            $this.parent().find('.toggle').removeClass('active');
        }
    }).find('.color-picker').wpColorPicker();

    // 分类按选择排序
    var $cat = $('.j-cat-sort');
    var cat_array = {};
    if($cat.length){
        for(var i=0;i<$cat.length;i++){
            var $name = $($cat[i]).data('name');
            cat_array[$name] = {};
            cat_array[$name]['y'] = [];
            cat_array[$name]['n'] = [];
            $($cat[i]).find('label').each(function (a, v) {
                if($(v).find('input').is(':checked')){
                    cat_array[$name]['y'].push({id: $(v).find('input').val(), name: $.trim($(v).text())});
                }else{
                    cat_array[$name]['n'].push({id: $(v).find('input').val(), name: $.trim($(v).text())});
                }
            });
        }
    }
    cats_render(cat_array);

    $cat.on('change', 'input', function () {
        var $this = $(this);
        var $name = $this.closest('.j-cat-sort').data('name');
        var checked = $this.is(':checked');
        for(var z=0;z<cat_array[$name][checked ? 'n' : 'y'].length;z++){
            if(cat_array[$name][checked ? 'n' : 'y'][z] && cat_array[$name][checked ? 'n' : 'y'][z].id==$this.val()){
                delete cat_array[$name][checked ? 'n' : 'y'][z];
            }
        }
        cat_array[$name][checked ? 'y' : 'n'].push({id:$this.val(), name: $.trim($this.parent().text())});
        cats_render(cat_array);
    });


    function cats_render(cat_array){
        for(var x in cat_array){
            var $el = $('[data-name="'+x+'"]');
            var $html = '';
            for(var b = 0;b<cat_array[x]['y'].length;b++){
                if(cat_array[x]['y'][b] && cat_array[x]['y'][b]['name']) $html += '<label class="checkbox-inline"><input name="'+x+'[]" checked type="checkbox" value="'+cat_array[x]['y'][b]['id']+'"> '+cat_array[x]['y'][b]['name']+'</label>';
            }
            for(var c = 0; c<cat_array[x]['n'].length; c++){
                if(cat_array[x]['n'][c] && cat_array[x]['n'][c]['name']) $html += '<label class="checkbox-inline"><input name="'+x+'[]" type="checkbox" value="'+cat_array[x]['n'][c]['id']+'"> '+cat_array[x]['n'][c]['name']+'</label>';
            }
            $el.html($html);
        }
    }

})(jQuery);