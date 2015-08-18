{$xssAlert = "' onclick='alert(\'xss is work!\');"}

<IMG SRC='{$xssAlert | html_attr}' >


{* <INPUT TYPE="IMAGE" SRC="javascript:alert('XSS');"> *}