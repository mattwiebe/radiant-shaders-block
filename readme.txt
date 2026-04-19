=== Radiant Shaders Block ===
Contributors: mattwiebe
Tags: gutenberg, block, background, animation, design
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Add animated Radiant shader backgrounds to block content with preset colors, theme color integration, and live parameter controls.

== Description ==

Radiant Shaders Block adds a dynamic background block powered by the Radiant shader collection. It is designed for modern block themes and page layouts where you want motion, texture, and atmosphere behind regular block content.

This plugin adapts the upstream Radiant shader project by Paul Bakaus for WordPress block workflows. Upstream project and credits:

* Paul Bakaus
* https://radiant-shaders.com/

Features include:

* A curated library of Radiant shader scenes
* Preset and theme-color-based color treatments
* Per-shader parameter controls in the editor
* Server-rendered frontend markup with direct iframe loading
* Nested block content layered above the animated shader background

Project homepage: https://github.com/mattwiebe/radiant-shaders-block

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/radiant-shaders-block` directory, or install the plugin through WordPress.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Insert the `Radiant Shader` block into a post, page, or template.
4. Choose a shader, adjust its colors and parameters, and add any nested content you want displayed above it.

== Frequently Asked Questions ==

= Does this block work with theme palette colors? =

Yes. The block can derive its color treatment from the active theme palette and lets you fine-tune the selected color in the editor.

= Does the frontend depend on WordPress view JavaScript? =

Newly saved blocks render directly from stored block settings. Legacy blocks without the newer stored iframe props fall back to a small compatibility initializer.

= Can I place other blocks on top of the shader? =

Yes. The shader runs as a background layer while nested blocks are rendered above it.

== Changelog ==

= 0.1.0 =

* Initial public release.
