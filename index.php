<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" type="text/css" href="uv/uv.css">
    <script src="uv/lib/offline.js"></script>
    <script src="uv/helpers.js"></script>
    <title>UV Hello World</title>
    <style>
        #uv {
            width: 800px;
            height: 600px;
        }
    </style>
</head>
<body>
    Hello
    <div id="uv" class="uv"></div>

    <script>
        window.addEventListener('uvLoaded', function (e) {
            createUV('#uv', {
                iiifResourceUri: 'http://wellcomelibrary.org/iiif/b18035723/manifest',
                configUri: 'uv-config.json'
            }, new UV.URLDataProvider());
        }, false);
    </script>

    <script src="uv-2.0.2/lib/viewer.js"></script>
</body>
</html>