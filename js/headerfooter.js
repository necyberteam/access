/**
 * @file
 * Header and footer JS.
 */

import {
  footer,
  footerMenus,
  header,
  siteMenus,
  universalMenus,
} from "https://esm.sh/@access-ci/ui@0.3.1";

(function (Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.accessMenuData = {
    attach: function (context, settings) {
      var currentMenu = drupalSettings.access.current_menu;
      var currentUri = drupalSettings.access.current_uri;
      try {
        currentMenu = JSON.parse(currentMenu);
      } catch (e) {
        console.error("Failed to parse currentMenu:", e);
      }
      setMenu(currentMenu, currentUri);
    }
  };
})(Drupal, drupalSettings);

function setMenu(menu, currentUri) {
  let mainMenu = menu;

  const siteItems = mainMenu;
  const isLoggedIn = document.body.classList.contains("user-logged-in");

  universalMenus({
    isLoggedIn: isLoggedIn,
    loginUrl: "/login?redirect=" + currentUri,
    logoutUrl: "/user/logout",
    siteName: "Support",
    target: document.getElementById("universal-menus"),
  });

  header({
    siteName: "Support",
    target: document.getElementById("header"),
  });

  siteMenus({
    items: siteItems,
    siteName: "Support",
    target: document.getElementById("site-menus"),
  });

  footerMenus({
    items: siteItems,
    target: document.getElementById("footer-menus"),
  });

  footer({
    target: document.getElementById("footer")
  });

};
