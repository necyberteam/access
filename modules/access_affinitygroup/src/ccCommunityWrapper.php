<?php

/**
 * @file
 * Returns the HTML to send to Constant Contact.
 * This is the email template for affinity groups in the Community category.
 *
 * NewsBody: the html for the main part of the message
 * newsTitle: line at the top
 * pubDate: date string to be used with [published: xxxx]
 * agNames: list of Affinity Group names for the 'You are receiving this email through...'
 * newsUrl: link for 'View on website' button.
 *
 * @todo possible refactor to combine with access theme template code.
 */

/**
 * NewsBody: the next text
 * newsTitle: headline
 * pubDate: date to display
 * agNames: array of affinity group names for top line
 * newsUrl: for button link to news item
 * logoUrl: url for logo image, or null for none.
 */
function ccCommunityNewsHTML($newsBody, $newsTitle, $pubDate, $agNames, $newsUrl, $logoUrl) {
    // Build list of one or more affinity group names separated by 'or'.
    $agText = '';
    $or = '';
    foreach ($agNames as $agName) {
        $agText = $agText . $or . $agName;
        $or = ' or ';
    }
    $agText = 'You are receiving this email through the ' . $agText . ' Affinity Group.';

    $pubDateDisplay = null;
    if ($pubDate) {
        $pubDateDisplay = <<<PUBDATE
        <table width="100%" border="0"
            cellpadding="0" cellspacing="0"
            style="table-layout:fixed;"
            class="text text--padding-vertical">
            <tbody>
                <tr>
                    <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                        align="left"
                        valign="top"
                        class="text_content-cell content-padding-horizontal">
                        <p style="margin:0;">
                            $pubDate
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    PUBDATE;
  }

  $logoDisplay = '';
  if ($logoUrl != NULL) {
    $logoDisplay = <<<LOGOHTML
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="image image--padding-vertical image--mobile-scale image--mobile-center">
            <tbody>
                <tr>
                    <td align="left" valign="top" style="padding:10px 40px;" class="image_container content-padding-horizontal">
                        <img width="240"
                            src="$logoUrl"
                            alt="Affinity group logo"
                            style="display:block;height:auto;max-width:100%;"
                            class="image_content">
                    </td>
                </tr>
            </tbody>
        </table>
    LOGOHTML;
  }

  // HTML with values for newsBody, newsTitle, pubdate and agText inserted.
  $emailText = <<<EMAILTEXT
  <html>
  <body>[[trackingImage]]
      <div id="">
          <style type="text/css">

            a.view-on-website-btn {
              font-family:Roboto,sans-serif;
              font-size: 16px!important;
              font-weight: bold!important;
              text-decoration: none!important;
              background-color:#ffc42d!important;
              border-color:#ffc42d!important;
              width: fit-content!important;
              color: #000000!important;
              padding: 10px 20px;
              border: 4px solid;
              margin-bottom:15px;
            }

            a.view-on-website-btn:hover {
              background-color: #ffffff!important;
              border-color:black!important;
              border: 4px solid;
            }

              @media only screen and (max-width:480px) {
                  # .footer-main-width {
                      width: 100% !important;
                  }

                  # .footer-mobile-hidden {
                      display: none !important;
                  }

                  # .footer-mobile-hidden {
                      display: none !important;
                  }

                  # .footer-column {
                      display: block !important;
                  }

                  # .footer-mobile-stack {
                      display: block !important;
                  }

                  # .footer-mobile-stack-padding {
                      padding-top: 3px;
                  }
              }

              .field--name-title, .field--name-recur-type, .field--name-event-instances .field__label, .field--name-field-affinity-group-node, .field--name-field-tags {
                display: none;
              }
              .field { margin: 15px 0; }

              img {}

              .layout {
                  min-width: 100%;
              }

              table {
                  table-layout: fixed;
              }

              .shell_outer-row {
                  table-layout: auto;
              }

                u+.body .shell_outer-row {
                  width: 700px;
              }

                .text .text_content-cell p {
                  margin: 0;
                  padding: 0;
                  margin-bottom: 0;
              }

               .text .text_content-cell ul,
               .text .text_content-cell ol {
                  padding: 0;
                  margin: 0 0 0 40px;
              }

               .text .text_content-cell li {
                  padding: 0;
                  margin: 0;
              }

                a {
                  text-decoration: underline;
              }

              a .filtered99999 {
                  text-decoration: underline !important;
                  font-size: inherit !important;
                  font-family: inherit !important;
                  font-weight: inherit !important;
                  line-height: inherit !important;
                  color: inherit !important;
              }

              .text .text_content-cell {}
          </style>
          <style>
              @media only screen and (max-width:480px) {
                   .button_content-cell {
                      padding-top: 10px !important;
                      padding-right: 20px !important;
                      padding-bottom: 10px !important;
                      padding-left: 20px !important;
                  }

                   .button_border-row .button_content-cell {
                      padding-top: 10px !important;
                      padding-right: 20px !important;
                      padding-bottom: 10px !important;
                      padding-left: 20px !important;
                  }

                   .column .content-padding-horizontal {
                      padding-left: 20px !important;
                      padding-right: 20px !important;
                  }

                   .layout .column .content-padding-horizontal .content-padding-horizontal {
                      padding-left: 0px !important;
                      padding-right: 0px !important;
                  }

                   .layout .column .content-padding-horizontal .block-wrapper_border-row .content-padding-horizontal {
                      padding-left: 20px !important;
                      padding-right: 20px !important;
                  }

                   .dataTable {
                      overflow: auto !important;
                  }

                   .dataTable .dataTable_content {
                      width: auto !important;
                  }

                   .image--mobile-scale .image_container img {
                      width: auto !important;
                  }

                   .image--mobile-center .image_container img {
                      margin-left: auto !important;
                      margin-right: auto !important;
                  }

                   .layout-margin .layout-margin_cell {
                      padding: 0px 20px !important;
                  }

                   .layout-margin--uniform .layout-margin_cell {
                      padding: 20px 20px !important;
                  }

                   .scale {
                      width: 100% !important;
                      height: auto !important;
                  }

                   .stack {
                      display: block !important;
                  }

                   .hide {
                      display: none !important;
                  }

                   u+.body .shell_outer-row {
                      width: 100% !important;
                  }

                   .socialFollow_container {
                      text-align: center !important;
                  }

                   .text .text_content-cell {
                      font-size: 16px !important;
                  }

                   .text .text_content-cell h1 {
                      font-size: 24px !important;
                  }

                   .text .text_content-cell h2 {
                      font-size: 20px !important;
                  }

                   .text .text_content-cell h3 {
                      font-size: 20px !important;
                  }

                   .text--sectionHeading .text_content-cell {
                      font-size: 24px !important;
                  }

                   .text--heading .text_content-cell {
                      font-size: 24px !important;
                  }

                   .text--dataTable .text_content-cell .dataTable .dataTable_content-cell {
                      font-size: 14px !important;
                  }

                   .text--dataTable .text_content-cell .dataTable th.dataTable_content-cell {}
              }
          </style>
          <div>

              <div lang="en-US" style="background-color:#138597;" class="shell">
                  <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#138597;"
                      bgcolor="#138597" class="shell_panel-row">
                      <tbody>
                          <tr>
                              <td style="" align="center" valign="top" class="shell_panel-cell">
                                  <table style="width:700px;" align="center" border="0" cellpadding="0" cellspacing="0"
                                      class="shell_width-row scale">
                                      <tbody>
                                          <tr>
                                              <td style="padding:15px 10px;" align="center" valign="top"
                                                  class="shell_width-cell">
                                                  <table width="100%" align="center" border="0" cellpadding="0"
                                                      cellspacing="0" class="shell_content-row">
                                                      <tbody>
                                                          <tr>
                                                              <td style="background-color:#ffffff;padding:0;"
                                                                  align="center" valign="top" bgcolor="#ffffff"
                                                                  class="shell_content-cell">
                                                                  <table
                                                                      style="background-color:#1a5b6e;table-layout:fixed;"
                                                                      width="100%" border="0" cellpadding="0"
                                                                      cellspacing="0" bgcolor="#1a5b6e"
                                                                      class="layout layout--1-column">
                                                                      <tbody>
                                                                          <tr>
                                                                              <td style="width:100%;" align="center"
                                                                                  valign="top"
                                                                                  class="column column--1 scale stack">

                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="text text--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                                                                                                  align="left"
                                                                                                  valign="top"
                                                                                                  class="text_content-cell content-padding-horizontal">
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
                                                                      class="layout layout--1-column">
                                                                      <tbody>
                                                                          <tr>
                                                                              <td style="width:100%;" align="center"
                                                                                  valign="top"
                                                                                  class="column column--1 scale stack">
                                                                                  <div style="line-height:30px;min-height:30px;"
                                                                                      class="spacer"> </div>
                                                                              </td>
                                                                          </tr>
                                                                      </tbody>
                                                                  </table>
                                                                  <table style="table-layout:fixed;" width="100%"
                                                                      border="0" cellpadding="0" cellspacing="0"
                                                                      class="layout layout--1-column">
                                                                      <tbody>
                                                                          <tr>
                                                                              <td style="width:100%;" align="center"
                                                                                  valign="top"
                                                                                  class="column column--1 scale stack">
                                                                                  $logoDisplay
                                                                                  <div style="line-height:10px;min-height:10px;"
                                                                                      class="spacer"> </div>
                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="text text--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                                                                                                  align="left"
                                                                                                  valign="top"
                                                                                                  class="text_content-cell content-padding-horizontal">
                                                                                                  <p style="margin:0;">
                                                                                                      <span style="font-size:16px;font-weight:bold;color:rgb(26, 91, 110);">
                                                                                                      $newsTitle
                                                                                                      </span>
                                                                                                  </p>
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>
                                                                                  $pubDateDisplay
                                                                                  <table width="100%" border="0"
                                                                                      cellpadding="0" cellspacing="0"
                                                                                      style="table-layout:fixed;"
                                                                                      class="text text--padding-vertical">
                                                                                      <tbody>
                                                                                          <tr>
                                                                                              <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:0px 40px;"
                                                                                                  align="left"
                                                                                                  valign="top"
                                                                                                  class="text_content-cell content-padding-horizontal">
                                                                                                  $newsBody
                                                                                              </td>
                                                                                          </tr>
                                                                                      </tbody>
                                                                                  </table>

                                                                                  <div style="line-height:20px;min-height:20px;"
                                                                                      class="spacer"> </div>

                                                                                  <div style="text-align:left; padding-left: 40px;padding-top:10px;padding-bottom:15px;">
                                                                                    <a href="$newsUrl" rel="nofollow noopener noreferrer"
                                                                                      class="view-on-website-btn">
                                                                                      VIEW ON WEBSITE
                                                                                    </a>
                                                                                  </div>

                                                                                  <div style="min-height:30px;line-height:30px;"
                                                                                      class="spacer"> </div>
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
