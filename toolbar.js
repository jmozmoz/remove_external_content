function toggle_cdm_expanded() {
    new Ajax.Request("backend.php", {
        parameters: "op=pluginhandler&plugin=remove_external_content&method=test_func&id=" + "testtesttest",
        onComplete: function(transport) {
            var reply =  JSON.parse(transport.responseText);
            var icon = document.getElementById("remove_external_content_icon");
            icon.setAttribute("class", reply.class_name);
            var button = document.getElementById("remove_external_content_button");
            button.setAttribute("title", reply.title);
            //alert(reply);
        } 
    });
}

