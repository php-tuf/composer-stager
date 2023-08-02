#!/usr/bin/env bash

# NAME
#     regenerate-fixtures.sh - Regenerate PHPBench test fixtures.
#
# SYNOPSIS
#     regenerate-fixtures.sh
#
# DESCRIPTION
#     Regenerate PHPBench performance testing fixtures. This should only be
#     used if, for some reason, a composer.json or composer.lock needs to be
#     updated, for example, due to critical security vulnerabilities in
#     dependencies. Other than that, this serves as executable documentation
#     of how the fixtures were originally generated.

regenerate_fixture () {
    VERSION="$1"; DIR=drupal-"$1"

    rm -rf "$DIR"

    # Start with the recommended project template.
    composer create-project \
        --ignore-platform-reqs \
        drupal/recommended-project:"$VERSION" \
        "$DIR"

    # Install the top ~125 most-installed Drupal modules.
    composer require \
        --prefer-dist \
        --fixed \
        --update-with-all-dependencies \
        --prefer-stable \
        --no-interaction \
        --no-plugins \
        --no-scripts \
        --working-dir="$DIR" \
        drupal/token \
        drupal/pathauto \
        drupal/admin_toolbar \
        drupal/ctools \
        drupal/metatag \
        drupal/entity_reference_revisions \
        drupal/paragraphs \
        drupal/jquery_ui \
        drupal/redirect \
        drupal/crop \
        drupal/jquery_ui_datepicker \
        drupal/search_api \
        drupal/jquery_ui_touch_punch \
        drupal/ckeditor \
        drupal/address \
        drupal/entity_browser \
        drupal/honeypot \
        drupal/config_filter \
        drupal/twig_tweak \
        drupal/simple_sitemap \
        drupal/twig_tweak \
        drupal/block_class \
        drupal/jquery_ui_touch_punch \
        drupal/module_filter \
        drupal/editor_advanced_link \
        drupal/address \
        drupal/superfish \
        drupal/recaptcha \
        drupal/ds \
        drupal/entity_browser \
        drupal/eu_cookie_compliance \
        drupal/views_infinite_scroll \
        drupal/google_tag \
        drupal/scheduler \
        drupal/diff \
        drupal/config_filter \
        drupal/smart_trim \
        drupal/easy_breadcrumb \
        drupal/svg_image \
        drupal/views_bootstrap \
        drupal/entity_embed \
        drupal/focal_point \
        drupal/migrate_plus \
        drupal/migrate_tools \
        drupal/fontawesome \
        drupal/view_unpublished \
        drupal/geofield \
        drupal/extlink \
        drupal/menu_link_attributes \
        drupal/search_api_solr \
        drupal/antibot \
        drupal/file_mdm \
        drupal/link_attributes \
        drupal/draggableviews \
        drupal/image_url_formatter \
        drupal/config_split \
        drupal/externalauth \
        drupal/chosen \
        drupal/dropzonejs \
        drupal/quick_node_clone \
        drupal/addtoany \
        drupal/auto_entitylabel \
        drupal/geolocation \
        drupal/autologout \
        drupal/maxlength \
        drupal/geocoder \
        drupal/flood_control \
        drupal/anchor_link \
        drupal/upgrade_status \
        drupal/views_conditional \
        drupal/menu_item_extras \
        drupal/layout_builder_restrictions \
        drupal/key \
        drupal/exclude_node_title \
        drupal/robotstxt \
        drupal/memcache \
        drupal/back_to_top \
        drupal/contact_storage \
        drupal/contact_block \
        drupal/override_node_options \
        drupal/editor_file \
        drupal/role_delegation \
        drupal/sophron \
        drupal/profile \
        drupal/encrypt \
        drupal/menu_admin_per_menu \
        drupal/coffee \
        drupal/mailchimp \
        drupal/redis \
        drupal/search_api_autocomplete \
        drupal/search404 \
        drupal/fences \
        drupal/acquia_connector \
        drupal/leaflet \
        drupal/layout_builder_modal \
        drupal/file_delete \
        drupal/security_review \
        drupal/menu_trail_by_path \
        drupal/field_validation \
        drupal/image_effects \
        drupal/entityqueue \
        drupal/purge \
        drupal/workbench \
        drupal/social_media_links \
        drupal/matomo \
        drupal/shield \
        drupal/entity_print \
        drupal/real_aes \
        drupal/imageapi_optimize \
        drupal/colorbutton \
        drupal/sharethis \
        drupal/smart_date \
        drupal/select2 \
        drupal/yoast_seo \
        drupal/media_library_form_element \
        drupal/recaptcha_v3 \
        drupal/taxonomy_manager \
        drupal/bootstrap_layouts \
        drupal/environment_indicator \
        drupal/cas \
        drupal/video \
        drupal/date_popup \
        drupal/dynamic_entity_reference \
        drupal/block_visibility_groups \
        drupal/layout_builder_styles \
        drupal/taxonomy_access_fix \
        drupal/path_redirect_import \
        drupal/transliterate_filenames \
        drupal/tablefield
}

regenerate_fixture "09.5.0"
regenerate_fixture "10.0.0"
regenerate_fixture "10.1.0"
regenerate_fixture "10.1.1"
