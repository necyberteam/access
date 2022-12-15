<?php

/**
 * @file
 * Returns the HTML to send to Constant Contact.
 *
 * This functions like an email template.
 *
 * NewsBody: the html for the main part of the message
 * newsTitle: line at the top
 * pubDate: date string to be used with [published: xxxx]
 * agNames: list of Affinity Group names for the 'You are receiving this email through...'
 * newsUrl: link for 'View on website' button .
 */

/**
 * @todo we will probably have to change the url of the access logo image when we close our test accounts
 * @todo this was lifted from constant contact's generated email.  replace generated names with more understandable names.
 */
function makeNewsHTML($newsBody, $newsTitle, $pubDate, $agNames, $newsUrl) {

  // Build list of one or more affinity group names separated by 'or'.
  $agText = '';
  $or = '';
  foreach ($agNames as $agName) {
    $agText = $agText . $or . $agName;
    $or = ' or ';
  }
  $agText = 'You are receiving this email through the ' . $agText . ' Affinity Group.';

  // HTML with values for newsBody, newsTitle, pubdate and agText inserted.
  $emailText = <<<EMAILTEXT
    <html>
  <body>[[trackingImage]]
      <div id="yiv2621404860">
          <style type="text/css">
              @media only screen and (max-width:480px) {
                  #yiv2621404860 .yiv2621404860footer-main-width {
                      width: 100% !important;
                  }

                  #yiv2621404860 .yiv2621404860footer-mobile-hidden {
                      display: none !important;
                  }

                  #yiv2621404860 .yiv2621404860footer-mobile-hidden {
                      display: none !important;
                  }

                  #yiv2621404860 .yiv2621404860footer-column {
                      display: block !important;
                  }

                  #yiv2621404860 .yiv2621404860footer-mobile-stack {
                      display: block !important;
                  }

                  #yiv2621404860 .yiv2621404860footer-mobile-stack-padding {
                      padding-top: 3px;
                  }
              }

              #yiv2621404860 #yiv2621404860 img {}

              #yiv2621404860 .yiv2621404860layout {
                  min-width: 100%;
              }

              #yiv2621404860 table {
                  table-layout: fixed;
              }

              #yiv2621404860 .yiv2621404860shell_outer-row {
                  table-layout: auto;
              }

              #yiv2621404860 #yiv2621404860 u+.yiv2621404860body .yiv2621404860shell_outer-row {
                  width: 700px;
              }

              #yiv2621404860 #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell p {
                  margin: 0;
                  padding: 0;
                  margin-bottom: 0;
              }

              #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell ul,
              #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell ol {
                  padding: 0;
                  margin: 0 0 0 40px;
              }

              #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell li {
                  padding: 0;
                  margin: 0;
              }

              #yiv2621404860 #yiv2621404860 a {
                  text-decoration: underline;
              }

              #yiv2621404860 #yiv2621404860 a .filtered99999 {
                  text-decoration: underline !important;
                  font-size: inherit !important;
                  font-family: inherit !important;
                  font-weight: inherit !important;
                  line-height: inherit !important;
                  color: inherit !important;
              }

              #yiv2621404860 #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell {}
          </style>
          <style>
              @media only screen and (max-width:480px) {
                  #yiv2621404860 .yiv2621404860button_content-cell {
                      padding-top: 10px !important;
                      padding-right: 20px !important;
                      padding-bottom: 10px !important;
                      padding-left: 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860button_border-row .yiv2621404860button_content-cell {
                      padding-top: 10px !important;
                      padding-right: 20px !important;
                      padding-bottom: 10px !important;
                      padding-left: 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860column .yiv2621404860content-padding-horizontal {
                      padding-left: 20px !important;
                      padding-right: 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860layout .yiv2621404860column .yiv2621404860content-padding-horizontal .yiv2621404860content-padding-horizontal {
                      padding-left: 0px !important;
                      padding-right: 0px !important;
                  }

                  #yiv2621404860 .yiv2621404860layout .yiv2621404860column .yiv2621404860content-padding-horizontal .yiv2621404860block-wrapper_border-row .yiv2621404860content-padding-horizontal {
                      padding-left: 20px !important;
                      padding-right: 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860dataTable {
                      overflow: auto !important;
                  }

                  #yiv2621404860 .yiv2621404860dataTable .yiv2621404860dataTable_content {
                      width: auto !important;
                  }

                  #yiv2621404860 .yiv2621404860image--mobile-scale .yiv2621404860image_container img {
                      width: auto !important;
                  }

                  #yiv2621404860 .yiv2621404860image--mobile-center .yiv2621404860image_container img {
                      margin-left: auto !important;
                      margin-right: auto !important;
                  }

                  #yiv2621404860 .yiv2621404860layout-margin .yiv2621404860layout-margin_cell {
                      padding: 0px 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860layout-margin--uniform .yiv2621404860layout-margin_cell {
                      padding: 20px 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860scale {
                      width: 100% !important;
                      height: auto !important;
                  }

                  #yiv2621404860 .yiv2621404860stack {
                      display: block !important;
                  }

                  #yiv2621404860 .yiv2621404860hide {
                      display: none !important;
                  }

                  #yiv2621404860 u+.yiv2621404860body .yiv2621404860shell_outer-row {
                      width: 100% !important;
                  }

                  #yiv2621404860 .yiv2621404860socialFollow_container {
                      text-align: center !important;
                  }

                  #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell {
                      font-size: 16px !important;
                  }

                  #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell h1 {
                      font-size: 24px !important;
                  }

                  #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell h2 {
                      font-size: 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860text .yiv2621404860text_content-cell h3 {
                      font-size: 20px !important;
                  }

                  #yiv2621404860 .yiv2621404860text--sectionHeading .yiv2621404860text_content-cell {
                      font-size: 24px !important;
                  }

                  #yiv2621404860 .yiv2621404860text--heading .yiv2621404860text_content-cell {
                      font-size: 24px !important;
                  }

                  #yiv2621404860 .yiv2621404860text--dataTable .yiv2621404860text_content-cell .yiv2621404860dataTable .yiv2621404860dataTable_content-cell {
                      font-size: 14px !important;
                  }

                  #yiv2621404860 .yiv2621404860text--dataTable .yiv2621404860text_content-cell .yiv2621404860dataTable th.yiv2621404860dataTable_content-cell {}
              }
          </style>
          <div>

              <div lang="en-US" style="background-color:#138597;" class="yiv2621404860shell">
                  <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#138597;"
                      bgcolor="#138597" class="yiv2621404860shell_panel-row">
                      <tbody>
                          <tr>
                              <td style="" align="center" valign="top" class="yiv2621404860shell_panel-cell">
                                  <table style="width:700px;" align="center" border="0" cellpadding="0" cellspacing="0"
                                      class="yiv2621404860shell_width-row yiv2621404860scale">
                                      <tbody>
                                          <tr>
                                              <td style="padding:15px 10px;" align="center" valign="top"
                                                  class="yiv2621404860shell_width-cell">
                                                  <table width="100%" align="center" border="0" cellpadding="0"
                                                      cellspacing="0" class="yiv2621404860shell_content-row">
                                                      <tbody>
                                                          <tr>
                                                              <td style="background-color:#ffffff;padding:0;"
                                                                  align="center" valign="top" bgcolor="#ffffff"
                                                                  class="yiv2621404860shell_content-cell">
                                                                  <table
                                                                      style="background-color:#1a5b6e;table-layout:fixed;"
                                                                      width="100%" border="0" cellpadding="0"
                                                                      cellspacing="0" bgcolor="#1a5b6e"
                                                                      class="yiv2621404860layout yiv2621404860layout--1-column">
                                                                      <tbody>
                                                                          <tr>
                                                                              <td style="width:100%;" align="center"
                                                                                  valign="top"
                                                                                  class="yiv2621404860column yiv2621404860column--1 yiv2621404860scale yiv2621404860stack">

                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="yiv2621404860text yiv2621404860text--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                                                                                                  align="left"
                                                                                                  valign="top"
                                                                                                  class="yiv2621404860text_content-cell yiv2621404860content-padding-horizontal">
                                                                                                  <p style="margin:0;">
                                                                                                      <span style="color:rgb(255, 255, 255);">
                                                                                                        $agText
                                                                                                      </span>
                                                                                                  </p>
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>
                                                                              </td>
                                                                          </tr>
                                                                      </tbody>
                                                                  </table>
                                                                  <table style="table-layout:fixed;" width="100%"
                                                                      border="0" cellpadding="0" cellspacing="0"
                                                                      class="yiv2621404860layout yiv2621404860layout--1-column">
                                                                      <tbody>
                                                                          <tr>
                                                                              <td style="width:100%;" align="center"
                                                                                  valign="top"
                                                                                  class="yiv2621404860column yiv2621404860column--1 yiv2621404860scale yiv2621404860stack">
                                                                                  <div style="line-height:30px;min-height:30px;"
                                                                                      class="yiv2621404860spacer"> </div>
                                                                              </td>
                                                                          </tr>
                                                                      </tbody>
                                                                  </table>
                                                                  <table style="table-layout:fixed;" width="100%"
                                                                      border="0" cellpadding="0" cellspacing="0"
                                                                      class="yiv2621404860layout yiv2621404860layout--1-column">
                                                                      <tbody>
                                                                          <tr>
                                                                              <td style="width:100%;" align="center"
                                                                                  valign="top"
                                                                                  class="yiv2621404860column yiv2621404860column--1 yiv2621404860scale yiv2621404860stack">
                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      class="yiv2621404860image yiv2621404860image--padding-vertical yiv2621404860image--mobile-scale yiv2621404860image--mobile-center">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td align="left"
                                                                                                  valign="top"
                                                                                                  style="padding:10px 40px;"
                                                                                                  class="yiv2621404860image_container yiv2621404860content-padding-horizontal">
                                                                                                  <img width="240"
                                                                                                      src="https://ecp.yusercontent.com/mail?url=https%3A%2F%2Ffiles.constantcontact.com%2F611b8b84901%2F3769f037-6aeb-4d73-baae-408c17803a76.jpg%3Frdr%3Dtrue&amp;t=1666141473&amp;ymreqid=e44e9186-6cc5-04bd-1ceb-710003012600&amp;sig=tCKknf09uZ.wgIRKOMIdqw--~D"
                                                                                                      alt=""
                                                                                                      style="display:block;height:auto;max-width:100%;"
                                                                                                      class="yiv2621404860image_content">
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>
                                                                                  <div style="line-height:10px;min-height:10px;"
                                                                                      class="yiv2621404860spacer"> </div>
                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="yiv2621404860text yiv2621404860text--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                                                                                                  align="left"
                                                                                                  valign="top"
                                                                                                  class="yiv2621404860text_content-cell yiv2621404860content-padding-horizontal">
                                                                                                  <p style="margin:0;">
                                                                                                      <span style="font-size:16px;font-weight:bold;color:rgb(26, 91, 110);">
                                                                                                      $newsTitle
                                                                                                      </span>
                                                                                                  </p>
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>
                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="yiv2621404860text yiv2621404860text--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                                                                                                  align="left"
                                                                                                  valign="top"
                                                                                                  class="yiv2621404860text_content-cell yiv2621404860content-padding-horizontal">
                                                                                                  <p style="margin:0;">
                                                                                                      [Published Date: $pubDate]
                                                                                                  </p>
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>
                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="yiv2621404860text yiv2621404860text--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                                                                                                  align="left"
                                                                                                  valign="top"
                                                                                                  class="yiv2621404860text_content-cell yiv2621404860content-padding-horizontal">
                                                                                                  $newsBody
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>

                                                                                  <div style="line-height:20px;min-height:20px;"
                                                                                      class="yiv2621404860spacer"> </div>
                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="yiv2621404860button yiv2621404860button--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td align="left"
                                                                                                  style="padding:10px 40px;"
                                                                                                  class="yiv2621404860button_container yiv2621404860content-padding-horizontal">
                                                                                                  <table
                                                                                                      style="background-color:#ffc42d;width:inherit;border-radius:2px;border-spacing:0;border:none;"
                                                                                                      border="0"
                                                                                                      cellpadding="0"
                                                                                                      cellspacing="0"
                                                                                                      bgcolor="#ffc42d"
                                                                                                      class="yiv2621404860button_content-row">
                                                                                                      <tbody>
                                                                                                          <tr>
                                                                                                              <td style="padding:10px 15px;"
                                                                                                                  align="center"
                                                                                                                  class="yiv2621404860button_content-cell">
                                                                                                                  <a href="$newsUrl"
                                                                                                                      rel="nofollow noopener noreferrer"
                                                                                                                      style="color:#000000;font-family:Arial, Verdana, Helvetica, sans-serif;font-size:16px;word-wrap:break-word;font-weight:bold;text-decoration:none;"
                                                                                                                      class="yiv2621404860button_link">
                                                                                                                      VIEW ON WEBSITE
                                                                                                                  </a>
                                                                                                              </td>
                                                                                                          </tr>
                                                                                                      </tbody>
                                                                                                  </table>
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>
                                                                                  <div style="min-height:30px;line-height:30px;"
                                                                                      class="yiv2621404860spacer"> </div>
                                                                              </td>
                                                                          </tr>
                                                                      </tbody>
                                                                  </table>
                                                              </td>
                                                          </tr>
                                                      </tbody>
                                                  </table>
                                              </td>
                                          </tr>
                                      </tbody>
                                  </table>
                              </td>
                          </tr>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </body>
  </html>
  EMAILTEXT;
  // note: EMAILTEXT must be to the left column-wise of the last tag (php)
  return $emailText;
}
