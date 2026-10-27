<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$pageTitle = 'Advanced Photo Studio';
$activePage = 'tools';
$pageScripts = [
    'assets/vendor/pdfjs/pdf.min.js',
    'assets/js/tools.js',
];
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero tools-hero section-space">
  <div class="container narrow center reveal">
    <span class="eyebrow">ADVANCED PHOTO & PDF STUDIO</span>
    <h1>FastNexora Print – All-in-One File Tools, Open to Everyone</h1>
    <p>Crop, resize, compress, convert images and PDFs locally. Background removal is available without signing in and is protected by a fair-use limit.</p>
  </div>
</section>

<section class="section-space compact-top" id="photo-studio">
  <div class="container">
    <div class="tool-tabs glass reveal" role="tablist" aria-label="Photo studio tools">
      <button type="button" class="active" data-tool-tab="crop"><span>01</span> Crop</button>
      <button type="button" data-tool-tab="background"><span>02</span> Remove BG</button>
      <button type="button" data-tool-tab="compress"><span>03</span> Compress</button>
      <button type="button" data-tool-tab="jpg-pdf"><span>04</span> JPG to PDF</button>
      <button type="button" data-tool-tab="pdf-jpg"><span>05</span> PDF to JPG</button>
    </div>

    <section class="tool-panel glass reveal" data-tool-panel="crop">
      <div class="tool-sidebar">
        <div class="tool-heading"><span class="micro-label">ADVANCED CROP</span><h2>Shape and enhance</h2><p>Drag to position, then resize and export in your preferred format.</p></div>
        <label class="upload-button">
          <input type="file" accept="image/jpeg,image/png,image/webp" data-crop-input>
          <span>Choose an image</span>
          <small>JPG, PNG, or WebP up to 12 MB</small>
        </label>
        <div class="tool-controls" data-crop-controls hidden>
          <label class="control-field"><span>Aspect ratio</span><select data-aspect><option value="free">Original / free</option><option value="1">Square 1:1</option><option value="1.333333" selected>Standard 4:3</option><option value="1.5">Photo 3:2</option><option value="1.777778">Wide 16:9</option><option value="0.8">Portrait 4:5</option><option value="0.75">Document 3:4</option></select></label>
          <label class="range-field"><span>Zoom <output data-zoom-output>100%</output></span><input type="range" min="100" max="300" value="100" data-zoom></label>
          <label class="range-field"><span>Rotation <output data-rotation-output>0&deg;</output></span><input type="range" min="-180" max="180" value="0" data-rotation></label>
          <label class="range-field"><span>Brightness <output data-brightness-output>100%</output></span><input type="range" min="50" max="150" value="100" data-brightness></label>
          <label class="range-field"><span>Contrast <output data-contrast-output>100%</output></span><input type="range" min="50" max="150" value="100" data-contrast></label>
          <label class="range-field"><span>Saturation <output data-saturation-output>100%</output></span><input type="range" min="0" max="200" value="100" data-saturation></label>
          <div class="mini-button-row"><button class="btn btn-ghost" type="button" data-flip-x>Flip H</button><button class="btn btn-ghost" type="button" data-flip-y>Flip V</button></div>
          <div class="form-row">
            <label class="control-field"><span>Width (px)</span><input type="number" min="1" max="8000" value="1200" data-crop-width></label>
            <label class="control-field"><span>Format</span><select data-crop-format><option value="image/jpeg">JPG</option><option value="image/png">PNG</option><option value="image/webp">WebP</option></select></label>
          </div>
          <label class="range-field"><span>Quality <output data-crop-quality-output>90%</output></span><input type="range" min="40" max="100" value="90" data-crop-quality></label>
          <div class="button-row"><button class="btn btn-ghost" type="button" data-crop-reset>Reset</button><button class="btn btn-dark" type="button" data-crop-download>Download</button></div>
        </div>
        <div class="privacy-inline"><span>&#9679;</span><p><strong>Local processing</strong><small>Your image stays in this browser.</small></p></div>
      </div>
      <div class="editor-stage">
        <div class="canvas-empty" data-crop-empty><span class="upload-illustration">+</span><h3>Your canvas is ready</h3><p>Choose an image to start editing.</p></div>
        <div class="crop-canvas-wrap checkerboard" data-crop-wrap hidden><canvas width="720" height="540" data-crop-canvas></canvas><span class="drag-hint">Drag image to reposition</span></div>
      </div>
    </section>

    <section class="tool-panel glass" data-tool-panel="background" hidden>
      <div class="tool-sidebar">
        <div class="tool-heading"><span class="micro-label">GUEST BACKGROUND REMOVER</span><h2>Create a clean cutout</h2><p>No login is required. The configured remove.bg service returns a transparent PNG.</p></div>
        <label class="upload-button"><input type="file" accept="image/jpeg,image/png,image/webp" data-bg-input><span>Choose an image</span><small>JPG, PNG, or WebP up to 12 MB</small></label>
        <button class="btn btn-dark btn-large full-width" type="button" data-bg-remove disabled><span class="button-label">Remove background</span><span class="button-loader"></span></button>
        <a class="btn btn-ghost full-width" data-bg-download hidden download="background-removed.png">Download PNG</a>
        <div class="form-result" data-bg-result role="status" hidden></div>
        <div class="privacy-inline"><span>&#9679;</span><p><strong>Public API processing</strong><small>Limited to 5 requests per IP each hour.</small></p></div>
      </div>
      <div class="editor-stage">
        <div class="compare-grid" data-bg-preview hidden><div><span>Original</span><div class="preview-frame"><img data-bg-original alt="Original preview"></div></div><div><span>Result</span><div class="preview-frame checkerboard"><img data-bg-output alt="Background removed preview"></div></div></div>
        <div class="canvas-empty" data-bg-empty><span class="upload-illustration cutout">&#9670;</span><h3>Transparent result preview</h3><p>Upload a person, product, vehicle, or object.</p></div>
      </div>
    </section>

    <section class="tool-panel glass" data-tool-panel="compress" hidden>
      <div class="tool-sidebar">
        <div class="tool-heading"><span class="micro-label">COMPRESS & CONVERT</span><h2>Make images lighter</h2><p>Resize and convert JPG, PNG, or WebP files entirely in your browser.</p></div>
        <label class="upload-button"><input type="file" accept="image/jpeg,image/png,image/webp" data-compress-input><span>Choose an image</span><small>Up to 20 MB</small></label>
        <div class="tool-controls">
          <label class="control-field"><span>Output format</span><select data-compress-format><option value="image/jpeg">JPG</option><option value="image/webp">WebP</option><option value="image/png">PNG</option></select></label>
          <label class="control-field"><span>Maximum width</span><select data-compress-width><option value="0">Keep original</option><option value="2560">2560 px</option><option value="1920" selected>1920 px</option><option value="1280">1280 px</option><option value="800">800 px</option></select></label>
          <label class="range-field"><span>Quality <output data-compress-quality-output>82%</output></span><input type="range" min="20" max="100" value="82" data-compress-quality></label>
          <button class="btn btn-dark btn-large full-width" type="button" data-compress-run disabled>Compress image</button>
          <a class="btn btn-ghost full-width" data-compress-download hidden>Download image</a>
          <div class="form-result" data-compress-result role="status" hidden></div>
        </div>
        <div class="privacy-inline"><span>&#9679;</span><p><strong>Private by design</strong><small>No upload and no account required.</small></p></div>
      </div>
      <div class="editor-stage">
        <div class="compare-grid" data-compress-preview hidden><div><span>Original</span><div class="preview-frame"><img data-compress-original alt="Original image"></div></div><div><span>Compressed</span><div class="preview-frame checkerboard"><img data-compress-output alt="Compressed image"></div></div></div>
        <div class="canvas-empty" data-compress-empty><span class="upload-illustration">%</span><h3>Smaller files, same purpose</h3><p>Select an image and choose the output settings.</p></div>
      </div>
    </section>

    <section class="tool-panel glass" data-tool-panel="jpg-pdf" id="jpg-pdf" hidden>
      <div class="tool-sidebar">
        <div class="tool-heading"><span class="micro-label">IMAGES TO PDF</span><h2>Build one PDF</h2><p>Choose several images. Their selected order becomes the PDF page order.</p></div>
        <label class="upload-button"><input type="file" accept="image/jpeg,image/png,image/webp" multiple data-jpg-pdf-input><span>Choose images</span><small>Up to 30 images, 20 MB each</small></label>
        <div class="tool-controls">
          <label class="control-field"><span>Page size</span><select data-pdf-page-size><option value="a4" selected>A4</option><option value="image">Fit each image</option></select></label>
          <label class="control-field"><span>Orientation</span><select data-pdf-orientation><option value="auto" selected>Automatic</option><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select></label>
          <label class="control-field"><span>Margin</span><select data-pdf-margin><option value="0">None</option><option value="18">Small</option><option value="36" selected>Normal</option><option value="54">Large</option></select></label>
          <label class="range-field"><span>Image quality <output data-pdf-quality-output>88%</output></span><input type="range" min="45" max="100" value="88" data-pdf-quality></label>
          <button class="btn btn-dark btn-large full-width" type="button" data-jpg-pdf-run disabled>Create PDF</button>
          <a class="btn btn-ghost full-width" data-jpg-pdf-download hidden download="images.pdf">Download PDF</a>
          <div class="form-result" data-jpg-pdf-result role="status" hidden></div>
        </div>
        <div class="privacy-inline"><span>&#9679;</span><p><strong>Local PDF creation</strong><small>Images never leave your device.</small></p></div>
      </div>
      <div class="editor-stage">
        <div class="file-workspace" data-jpg-pdf-list><div class="canvas-empty"><span class="upload-illustration">PDF</span><h3>Add images to begin</h3><p>The list will show each planned PDF page.</p></div></div>
      </div>
    </section>

    <section class="tool-panel glass" data-tool-panel="pdf-jpg" id="pdf-jpg" hidden>
      <div class="tool-sidebar">
        <div class="tool-heading"><span class="micro-label">PDF TO JPG</span><h2>Export every page</h2><p>Render up to 30 PDF pages as high-quality JPG images in this browser.</p></div>
        <label class="upload-button"><input type="file" accept="application/pdf" data-pdf-jpg-input><span>Choose a PDF</span><small>PDF up to 50 MB and 30 pages</small></label>
        <div class="tool-controls">
          <label class="control-field"><span>Resolution</span><select data-pdf-jpg-scale><option value="1">Standard</option><option value="1.5" selected>High</option><option value="2">Very high</option></select></label>
          <label class="range-field"><span>JPG quality <output data-pdf-jpg-quality-output>90%</output></span><input type="range" min="50" max="100" value="90" data-pdf-jpg-quality></label>
          <button class="btn btn-dark btn-large full-width" type="button" data-pdf-jpg-run disabled>Convert PDF</button>
          <button class="btn btn-ghost full-width" type="button" data-pdf-jpg-all hidden>Download all JPGs</button>
          <div class="form-result" data-pdf-jpg-result role="status" hidden></div>
        </div>
        <div class="privacy-inline"><span>&#9679;</span><p><strong>Local PDF rendering</strong><small>Your document is not uploaded.</small></p></div>
      </div>
      <div class="editor-stage">
        <div class="file-workspace result-gallery" data-pdf-jpg-gallery><div class="canvas-empty"><span class="upload-illustration">JPG</span><h3>PDF pages appear here</h3><p>Choose a PDF and select the output quality.</p></div></div>
      </div>
    </section>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
