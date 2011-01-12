<?php return <<<HTML
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
    <title>{$title}</title>
    <style>
    body{
        font-family:arial,sans-serif;
        padding:0px;
        margin:0px;
        height:100%;
    }
    {$css}
    </style>
</head>

<body>
  {$nav}
  <div class="main" style="padding-left:5px;">
    {$main}
  </div>

  <script>
    {$js}
  </script>
</body>
</html>
HTML;
