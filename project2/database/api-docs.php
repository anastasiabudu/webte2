<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="SwaggerUI" />
  <title>SwaggerUI</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
  <style>
    /* Стили для кнопки "Назад" */
    .back-button {
      position: relative;
      bottom: 20px;
      left: 20px;
      padding: 10px 20px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      z-index: 1000;
    }
    .back-button:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>
  <div id="swagger-ui"></div>
  <!-- Кнопка "Назад" -->
  <button class="back-button" onclick="window.location.href='index.html'">Back</button>

  <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js" crossorigin></script>
  <script>
    window.onload = () => {
      window.ui = SwaggerUIBundle({
        url: './apidoc.yaml',
        dom_id: '#swagger-ui',
      });
    };
  </script>
</body>
</html>