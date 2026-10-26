<?php
/**
 * Content Security Policy header for the whole application.
 * Allows resources only from self and trusted CDNs.
 */
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: blob:; font-src 'self' https://cdn.jsdelivr.net; object-src 'none'; base-uri 'self'; form-action 'self';");
?>
