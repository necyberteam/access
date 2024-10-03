/**
 * @file
 * Contains the definition of the behaviour jsTestRedWeight.
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
      const currentMenu = drupalSettings.access.current_menu;
      var menu = [{name: 'Quick Links', items: [{name: 'Create an Account', href: 'https://operations.access-ci.org/identity/new-user'},{name: 'Open a Help Ticket', href: 'https://support.access-ci.org/help-ticket'},{name: 'ACCESS Resource Advisor (ARA)', href: 'https://access-ara.ccs.uky.edu:8080'},{name: 'Search ACCESS', href: '/find'},{name: 'View your Help Tickets', href: 'https://access-ci.atlassian.net/servicedesk/customer/user/requests'},{name: 'Software Documentation Service (SDS)', href: 'https://access-sds.ccs.uky.edu:8080'},]},{name: 'Community', items: [{name: 'Affinity Groups', href: '/affinity_groups'},{name: 'Community of Communities', href: 'https://coco.cyberinfrastructure.org'},{name: 'CSSN', href: '/community/cssn'},{name: 'Overview', href: '/community/overview'},{name: 'SCIPE/CIP', href: '/community/scipe'},]},{name: 'CCEP', items: [{name: 'Overview', href: '/ccep/overview'},{name: 'Workforce Development Workshops', href: 'https://support.access-ci.org/ccep-workforce-development-funded-workshops'},]},{name: 'Knowledge Base', items: [{name: 'Documentation', href: 'https://access-ci.atlassian.net/wiki/spaces/ACCESSdocumentation/'},{name: 'Ask.CI Forum', href: 'https://ask.ci/'},{name: 'Resources', href: '/knowledge-base/resources'},{name: 'Overview', href: '/knowledge-base/overview'},{name: 'Video Learning Center', href: 'https://support.access-ci.org/video-learning-center'},]},{name: 'MATCH Services', items: [{name: 'Overview', href: '/match/overview'},{name: 'Submissions', href: '/match-engagements-submissions'},{name: 'Interested People', href: '/match-interested-users'},{name: 'Engagements', href: '/match/engagements'},]},{name: 'Tools', items: [{name: 'OnDemand', href: '/tools/ondemand'},{name: 'Pegasus Workflows', href: '/tools/pegasus'},{name: 'XDMoD', href: '/tools/xdmod'},{name: 'Science Gateways', href: '/science-gateways'},{name: 'Overview', href: '/tools/overview'},{name: 'ACCESS Resource Advisor (ARA)', href: 'https://access-ara.ccs.uky.edu:8080/'},{name: 'Software Documentation Service (SDS)', href: 'https://access-sds.ccs.uky.edu:8080'},]},];
      setMenu(menu);
    }
  };
})(Drupal, drupalSettings);

function setMenu(menu) {
  let mainMenu = menu;

  const siteItems = mainMenu;


  universalMenus({
    loginUrl: "/login",
    logoutUrl: "/logout",
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
