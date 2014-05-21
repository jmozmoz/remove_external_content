<?php
global $link;

echo stylesheet_tag("plugins/remove_external_content/css/font-awesome.css");
print_user_stylesheet($link);

?>

<button id="remove_external_content_button" class="button_nav" title="<?php 

$icon_text  = $this->get_icon_text();
echo "$icon_text";

?>" onclick="toggle_cdm_expanded()">
<i id="remove_external_content_icon" class="<?php 

$icon_class = $this->get_icon_class();
echo $icon_class;

?>"></i></button>


