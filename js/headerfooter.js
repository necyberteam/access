/**
 * @file
 * Header and footer JS.
 */

import {
  header,
  siteMenus,
  tableOfContents,
  universalMenuItems,
  universalMenus,
} from "https://esm.sh/@access-ci/ui@0.3.0-beta1";

(function (Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.accessMenuData = {
    attach: function (context, settings) {
      var currentMenu = drupalSettings.access.current_menu;
      try {
        currentMenu = JSON.parse(currentMenu);
      } catch (e) {
        console.error("Failed to parse currentMenu:", e);
      }
      setMenu(currentMenu);
    }
  };
})(Drupal, drupalSettings);

function setMenu(menu) {
  let mainMenu = menu;

  const siteItems = mainMenu;
  const isLoggedIn = document.body.classList.contains("user-logged-in");

  universalMenus({
    isLoggedIn: isLoggedIn,
    loginUrl: "/login",
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

};
