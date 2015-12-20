{$xssAlert = "' onclick='alert(\'xss is work!\');"}

<IMG SRC='{$xssAlert | attr}' >


{* <INPUT TYPE="IMAGE" SRC="javascript:alert('XSS');"> *}