jQuery.fn.exists = function () {
    return jQuery(this).length > 0;
}

jQuery(document).ready(function($) {
    $(".inside #edit-slug-box").remove();
    
    guiders.createGuider({
        buttons: [{
            name:"No", 
            onclick: guiders.hideAll
        },{
            name:"Next",
            onclick:guiders.next()
        }],
        description: "Thank you installing Responsive Slider Wordpress Plugin,\n\
                        <br />Do you need a tutorial?",
        id: "first",
        next: "second",
        overlay: true,
        title: "Hi from Responsive Slider"
    });

    
    
    guiders.createGuider({
        attachTo: "#title",
        buttons: [{
            name: "Close"
        },

        {
            name: "Next"
        }],
        description: " Enter your slide show name",
        id: "second",
        next: "third",
        position: 7,
        title: "Slide Show Title"
    });
    
    
    
    guiders.createGuider({
        attachTo: "#slideshow_config",
        buttons: [{
            name: "Close"
        },

        {
            name: "Next"
        }],
        description: "You may configure your slideshow settings",
        id: "third",
        next: "fourth",
        position: 12,
        title: "Slideshow settings"
    });
    
    guiders.createGuider({
        attachTo: "#picture_upload",
        buttons: [{
            name: "Close"
        },

        {
            name: "Next"
        }],
        description: "To upload pictures click on Select Image buton, after successfull upload, you can order the \n\
            images by dragging them",
        id: "fourth",
        next: "fifth",
        position: 12,
        title: "Uploading Images"
    });
    
    
    
    guiders.createGuider({
        attachTo: "#unique_metabox",
          buttons: [{
            name: "Close"
        }],
        description: "Once, you have published your slideshow, you may copy paste this shortcode, in your posts\n\
\n\so the slideshow appears",
        id: "fifth",
        position: 9,
        title: "Shortcode"
    });
    
    
    var cookie_value = responsive_get_cookie('responsive_guider');
    if(cookie_value == false){
        responsive_set_cookie('responsive_guider','enabled',200);
        guiders.show('first');
    }
    
    
    $('#tour-guide-btn').click(function(){
        guiders.show('second');
    });
    
    if($(".plupload-upload-uic").exists()) {
        var pconfig=false;
        $(".plupload-upload-uic").each(function() {
            var $this=$(this);
            var id1=$this.attr("id");
            var imgId=id1.replace("plupload-upload-ui", "");
 
            plu_show_thumbs(imgId);
 
            pconfig=JSON.parse(JSON.stringify(base_plupload_config));
 
            pconfig["browse_button"] = imgId + pconfig["browse_button"];
            pconfig["container"] = imgId + pconfig["container"];
            pconfig["drop_element"] = imgId + pconfig["drop_element"];
            pconfig["file_data_name"] = imgId + pconfig["file_data_name"];
            pconfig["multipart_params"]["imgid"] = imgId;
            pconfig["multipart_params"]["_ajax_nonce"] = $this.find(".ajaxnonceplu").attr("id").replace("ajaxnonceplu", "");
            if($this.hasClass("plupload-upload-uic-multiple")) {
                pconfig["multi_selection"]=true;
            }
 
            if($this.find(".plupload-resize").exists()) {
                var w=parseInt($this.find(".plupload-width").attr("id").replace("plupload-width", ""));
                var h=parseInt($this.find(".plupload-height").attr("id").replace("plupload-height", ""));
                pconfig["resize"]={
                    width : w,
                    height : h,
                    quality : 90
                };
            }
 
            var uploader = new plupload.Uploader(pconfig);
 
            uploader.bind('Init', function(up){
                });
 
            uploader.init();
 
            // a file was added in the queue
            uploader.bind('FilesAdded', function(up, files){
                $.each(files, function(i, file) {
                    $this.find('.filelist').append(
                        '<div class="file" id="' + file.id + '"><b>' +
                        file.name + '</b> (<span>' + plupload.formatSize(0) + '</span>/' + plupload.formatSize(file.size) + ') ' +
                        '<div class="fileprogress"></div></div>');
                });
 
                up.refresh();
                up.start();
            });
 
            uploader.bind('UploadProgress', function(up, file) {
 
                $('#' + file.id + " .fileprogress").width(file.percent + "%");
                $('#' + file.id + " span").html(plupload.formatSize(parseInt(file.size * file.percent / 100)));
            });
 
            // a file was uploaded
            uploader.bind('FileUploaded', function(up, file, response) {
 
 
                $('#' + file.id).fadeOut();
                response=response["response"]
                // add url to the hidden field
                if($this.hasClass("plupload-upload-uic-multiple")) {
                    // multiple
                    var v1=$.trim($("#" + imgId).val());
                    if(v1) {
                        v1 = v1 + "," + response;
                    }
                    else {
                        v1 = response;
                    }
                    $("#" + imgId).val(v1);
                }
                else {
                    // single
                    $("#" + imgId).val(response + "");
                }
 
                // show thumbs 
                plu_show_thumbs(imgId);
            });
 
        });
    }
    
    
});

function plu_show_thumbs(imgId) {
    var $=jQuery;
    var thumbsC=$("#" + imgId + "plupload-thumbs");
    thumbsC.html("");
    // get urls
    var imagesS=$("#"+imgId).val();
    var images=imagesS.split(",");
    for(var i=0; i<images.length; i++) {
        if(images[i]) {
            var thumb=$('<li class="thumb" id="thumb' + imgId +  i + '"><img src="' + images[i] + '" alt="" /><div class="thumbi"><a id="thumbremovelink' + imgId + i + '" href="#">Remove</a></li>');
            thumbsC.append(thumb);
            thumb.find("a").click(function() {
                var ki=$(this).attr("id").replace("thumbremovelink" + imgId , "");
                ki=parseInt(ki);
                var kimages=[];
                imagesS=$("#"+imgId).val();
                images=imagesS.split(",");
                for(var j=0; j<images.length; j++) {
                    if(j != ki) {
                        kimages[kimages.length] = images[j];
                    }
                }
                $("#"+imgId).val(kimages.join());
                plu_show_thumbs(imgId);
                return false;
            });
        }
    }
    if(images.length > 1) {
        thumbsC.sortable({
            update: function(event, ui) {
                var kimages=[];
                thumbsC.find("img").each(function() {
                    kimages[kimages.length]=$(this).attr("src");
                    $("#"+imgId).val(kimages.join());
                    plu_show_thumbs(imgId);
                });
            }
        });
        thumbsC.disableSelection();
    }
}
function responsive_set_cookie(name, value,days)
{
    var expireDate = new Date()
    expireDate.setDate(expireDate.getDate()+days)
    document.cookie = name+"="+value+"; path=/; expires="+expireDate.toGMTString()
}

function responsive_get_cookie(w)
{
    cName = "";
    pCOOKIES = new Array();
    pCOOKIES = document.cookie.split('; ');
    for(bb = 0; bb < pCOOKIES.length; bb++){
        NmeVal  = new Array();
        NmeVal  = pCOOKIES[bb].split('=');
        if(NmeVal[0] == w){
            cName = unescape(NmeVal[0]);
        }
    }
    return cName;
 
}