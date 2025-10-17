<?php
namespace JLTRY\Plugin\Content\JOFavorites\Field;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects



class AjaxuploadField extends FormField
{
    protected $type = 'AjaxUpload';

    protected function getInput()
    {
        $html = [];
        $id = $this->id;
        $name = $this->name;
        $value = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');
        $fieldName = $this->fieldname;

        // AJAX endpoint (You will create this in your plugin)
        $ajaxUrl = Uri::base() . 'index.php?option=com_ajax&plugin=jofavorites&format=json&group=content&method=upload&XDEBUG_SESSION_START=test';

        $html[] = '<input type="hidden" id="' . $id . '" name="' . $name . '" value="' . $value . '" />';
        $html[] = '<input type="file" id="' . $id . '_file" />';
        $html[] = '<div id="' . $id . '_preview">';
        if ($value) {
            $html[] = '<span>Current file: ' . htmlspecialchars($value) . '</span>';
        }
        $html[] = '</div>';
        $html[] = '
<script>
document.getElementById("' . $id . '_file").addEventListener("change", function(e) {
    var file = this.files[0];
    if (!file) return;
    var formData = new FormData();
    formData.append("file", file);
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "' . $ajaxUrl . '", true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.data && resp.data[0].path) {
                    document.getElementById("' . $id . '").value = resp.data[0].path;
                document.getElementById("' . $id . '_preview").innerHTML = "<span>Uploaded: " + resp.data[0].path + "</span>";
                } else {
                    alert("Upload failed: " + (resp.message || "Unknown error"));
                }
            } catch (e) {
                alert("Server error: " + xhr.responseText);
            }
        } else {
            alert("Upload failed.");
        }
    };
    xhr.send(formData);
});
</script>';

        return implode("\n", $html);
    }
}