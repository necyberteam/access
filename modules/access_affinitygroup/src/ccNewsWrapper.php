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

 //
function sectionHeadHTML($titleText)
{
  $sectionHead = <<<SECTIONHEADHTML
    <table class="layout layout-feature layout-1-column" style="table-layout:fixed; background-color=#ffffff;" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
      <tr>
        <td class="column column--1 scale stack" style="width:100%;" align="center" valign="top">
          <table class="text text--feature text--padding-vertical" width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout: fixed;">
            <tr>
              <td class="text_content-cell content-padding-horizontal" style="text-align: center; font-family: Roboto,sans-serif; color:#4d4d4d; font-size:14px; line-height:1.2; display:block; word-wrap: break-word; padding: 10px 40px;" align="center" valign="top">
                <h1 style="text-align:left; font-family:Roboto,sans-serif; color: #3E3E3E; font-size: 24px; font-weight:bold; margin:0;" align="left">
                  <span style="font-family:Roboto,sans-serif; color:rgb(0, 92, 111); font-weight:bold;">$titleText</span>
                </h1>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
SECTIONHEADHTML;
  return $sectionHead;
}

function newsItemHTML($title, $pubDate, $body, $articleUrl)
{
    $main = "<div>
      <span>Published: $pubDate</span>
      <br><p>$body</p>
      </div>";
    return itemHTML($title, $main, $articleUrl, "News");
}

// each event or news it
function eventItemHTML($title, $eventDate, $description, $location, $articleUrl)
{
  $main = "<div>
        <span>$eventDate</span>
        <span>$description</span>
        <p>Location: $location</p>
      </div>";
  return itemHTML($title, $main, $articleUrl, "Event");
}
// each event or news it
function itemHTML($title, $main, $itemUrl, $itemType)
{
  $article = <<<ARTICLEHTML
  <table class="layout layout--1-column" style="table-layout: fixed;" width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td class="column column--1 scale stack" style="width:=65%;" align="center" valign="top">
        <table class="text text--article text--padding-vertical" width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
          <tr>
            <td class="text_content-cell=content-padding-horizontal" style="text-align: left; font-family:Roboto,sans-serif; color: #4d4d4d; font-size: 14px; line-height: 1.2; display: block; word-wrap: break-word; padding: 10px 20px 10px 40px;" align="left" valign="top">
              <h3 style="font-family:Roboto,sans-serif; color: #f07537; font-size: 18px; font-weight: bold; margin: 0;">
                $title
              </h3>
              <p style="margin:0;"><br></p>
              <p style="margin:0;"><span style="font-size: 14px;">$main</span></p>
              <p style="margin: 0;"><br></p>
              <p style="margin: 0;">
                <a href="$itemUrl" rel="nofollow noopener noreferrer"
                  style="color: #48c0b9; font-weight: bold; text-decoration: none;">$itemType Link</a>
              </p>
          </td>
        </tr>
      </table>
    </td>
    </tr>
  </table>
ARTICLEHTML;   // this text must positioned to the left of end html
  return $article;
}

// a line between articles
function dividerHTML()
{
    $divider = <<<DIVIDERHTML
  <table class="layout=layout--1-column" style="table-layout:fixed;"width="100%" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td class="column column--1 scale stack" style="width:100%;"align="center" valign="top">
                              <table class="divider" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                  <td class="divider_container content-padding-horizontal" style="padding: 10px 40px;" width="100%" align="center" valign="top">
                                    <table class="divider_content-row" style="height:1px; width:100%;" cellpadding="0" cellspacing="0" border="0">
                                      <tr>
                                        <td class="divider_content-cell" style="background-color:#d6d6d6; height:1px; line-height:1px; padding-bottom:0px; border-bottom-width:0px;" height="1" align="center" bgcolor="#d6d6d6">
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
  </table>
DIVIDERHTML;
    return $divider;
}

function ccNewsRollupHTML($news, $events)
{
  $newsBody = '<div class="access-news-rollup-email">'
              . '<div class="access-news-rollup-news">' . $news . '</div>'
              . dividerHTML()
              . '<div class="access-news-rollup-events">' . $events . '</div>'
              . '</div>';

  return ccNewsCommonHTML($newsBody);
}

function ccNewsSingleHTML($newsBody, $newsTitle, $pubDate, $agNames, $newsUrl)
{
    // Build list of one or more affinity group names separated by 'or'.
    // todo - add in button and title
    $agText = '';
    $or = '';
    foreach ($agNames as $agName) {
        $agText = $agText . $or . $agName;
        $or = ' or ';
    }
    $agText = 'You are receiving this email through the ' . $agText . ' Affinity Group.';

    $pubDateDisplay = '';
    if ($pubDate) {
        $pubDateDisplay = <<<PUBDATE
        <table width="100%" border="0"
          cellpadding="0" cellspacing="0"
          style="table-layout:fixed;"
          class="ag-table">
          <tbody>
          <tr>
            <td style="text-align:left;font-family:Arial, Verdana, Helvetica, sans-serif;color:#3E3E3E;font-size:14px;line-height:1.2;display:block;word-wrap:break-word;padding:10px 40px;"
                        align="left"
                        valign="top">
              <p style="margin:0;">
                [Published Date: $pubDate]
              </p>
            </td>
          </tr>
        </tbody>
      </table>
    PUBDATE;
    }
    return ccNewsCommonHTML($newsBody);
}

function ccNewsCommonHTML($newsBody)
{
     // HTML with values for newsBody, newsTitle, pubdate and agText inserted.
    $emailText = <<<EMAILTEXT
<html lang="en-US">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style type="text/css" data-premailer="ignore">
      @media only screen and (max-width:480px) {
        .footer-main-width {
          width: 100% !important;
        }

        .footer-mobile-hidden {
          display: none !important;
        }

        .footer-mobile-hidden {
          display: none !important;
        }

        .footer-column {
          display: block !important;
        }

        .footer-mobile-stack {
          display: block !important;
        }

        .footer-mobile-stack-padding {
          padding-top: 3px;
        }
      }

      /* IE: correctly scale images with w/h attbs */
      img {
        -ms-interpolation-mode: bicubic;
      }

      .layout {
        min-width: 100%;
      }

      table {
        table-layout: fixed;
      }

      .shell_outer-row {
        table-layout: auto;
      }

      /* Gmail/Web viewport fix */
      u+.body .shell_outer-row {
        width: 700px;
      }

      @media screen {
        @font-face {
          font-family: 'Roboto';
          font-style: normal;
          font-weight: 400;
          src: local('Roboto'), local('Roboto-Regular'), url(https://fonts.gstatic.com/s/roboto/v18/KFOmCnqEu92Fr1Mu4mxKKTU1Kg.woff2) format('woff2');
          unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215;
        }
      }

      /* LIST AND p STYLE=
 OVERRIDES */
      .text .text_content-cell p {
        margin: 0;
        padding: 0;
        margin-bottom: 0;
      }

      .text .text_content-cell ul,
      .text .text_content-cell ol {
        paddi=ng: 0;
        margin: 0 0 0 40px;
      }

      .text .text_content-cell li {
        padding: 0;
        margin: 0;
        /* line-height: 1.2; Remove after testing */
      }

      /* Text Link Style Reset */
      a {
        text-decoration: underline;
      }

      /* iOS: Autolink styles inherited*/
      a[x-apple-data-detectors] {
        text-decoration: underline !important;
        font-size: inherit !important;
        font-family: inherit !important;
        font-weight: inherit !important;
        line-height: inherit !important;
        color: inherit !important;
      }

      /* FF/Chrome: Smooth font rendering */
      .text .text_content-cell {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }
    </style>
    <!--[if gte mso 9]>
				<style id="ol-styles">
	/* OUTLOOK-SPECIFIC STYLES */ li { text-indent: -1em; padding: 0; margin: 0; /* line-height: 1.2; Remove after testing */ }
	ul, ol { padding: 0; margi=
		n: 0 0 0 40px; }
	p { margin: 0; padding: 0; margin-bottom: 0; }=20
	</style>
				<![endif]-->
    <style>
      @media only screen and (max-width:480px) {
        .button_content-cell {
          padding-top: 10px !important;
          padding-right: 20px !important;
          padding-botto=m: 10px !important;
          padding-left: 20px !important;
        }

        .button_border-row .button_content-cell {
          padding-top: 10px !important;
          padding-right: 20px !important;
          padding-botto=m: 10px !important;
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
        }

        .stack {
          display: block !important;
          box-sizing: border-box;
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

        .text--dataTable .text_content-cell .dataTable th.dataTable_content-cell {
          font-size: px !important;
        }
      }
    </style>
  </head>
  <body class="body template template--en-US" data-template-version="1.20.1" data-canonical-name="CPE-PT17831"  align="center" style="-ms-text-size-adjust:100%; -webkit-text-size-adjust: 100%; min-width: 00%; width: 100%; margin: 0px; padding: 0px;">
  [[trackingImage]]
    <div id="tracking-image" style="color: transparent; display: none; font-size: 1px; line-height: 1px; max-height: 0px; max=-width: 0px; opacity: 0; overflow: hidden;"></div>
    <div class="shell" lang="en-US" style="background-color:#1a5b6e;">
      <table class="shell_panel-row" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#1a5b6e;" bgcolor="#1a5b6e">
        <tr class="" >
          <td class="shell_panel-cell" style="" align="center" valign="top">
            <table class="shell_width-row scale" style="width: 700px;" align="center" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td class="shell_width-cell" style="padding: 15px 10px;" align="center" valign="top">
                  <table class="shell_content-row" width="100%" align="center" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                      <td class="shell_content-cell" style="background-color: #FFFFFF; padding: 0; border: 0px solid #3e3e3e;" align="center" valign="top" bgcolor="#FFFFFF">
                        <table class="layout layout--1-column" style="table-layout: fixed;" width="100%" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td class="column column--1 scale stack" style="width: 100%;"  align="center" valign="top">
                              <table class="image image--mobile-scale image--mobile-center"  width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                  <td class="image_container" align="center" valign="top">
                                    <img data-image-content class="image_content" width="680" src="https://ci4.googleusercontent.com/proxy/xcHw8mUnG_iWFWR9dul6oQMRuNJ9VGNEfZpSoOS4L2XQgZJuTT-cHwdXG-8XNBo8hUmkN_lF3dom96zj9NV1yjZ9e_T_PvmNI5s9pr2ARCURoNfSg-DhKLSXeHo_dWKnDCKYmg-l-JoX=s0-d-e1-ft#https://files.constantcontact.com/db08bd92701/8e5fb40a-35cc-4141-8f25-37caaed9ad80.jpg" alt="" style="display: block; height: auto; max-width=100%;">
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>

                        $newsBody

                        <table class="layout layout-1-column" style="table-layout: fixed;"  width="100%" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td class="column column-1 scale stack" style="width: 100%;"  align="center" valign="top">
                              <div class="spacer" style="line-height: 13px; height: 13px;">&#x200a;</div>
                            </td>
                          </tr>
                        </table>
                        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width:100%">
                          <tbody>
                            <tr>
                              <td align="center" valign="top" style="width:680px">
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="table-layout:fixed">
                                  <tbody>
                                    <tr>
                                      <td width="100%" align="center" valign="top" style="padding-bottom:6px;padding-top:10px">
                                        <table cellpadding="0" cellspacing="0" border="0" style="table-layout:fixed;height:1px;width:680px">
                                          <tbody>
                                            <tr>
                                              <td height="1" align="center" bgcolor="#ffc42d" style="padding-bottom:4px;background-color:rgb(255,196,45);height:1px;line-height:1px;border-bottom-width:0px">
                                                <img alt="" width="5" height="1" border="0" hspace="0" vspace="0" src="https://ci5.googleusercontent.com/proxy/prjVWi9agcvHo6wWwSY0NoWHiaFTUW1GFE88HIUk5LrHN5aeEIX3D6pJtDlEPNI6Dvf_Ou5XHLexQ1ajT_5sVXHMGfcLsqoinYvkNDmXc8HzvBff2Y637Q=s0-d-e1-ft#https://imgssl.constantcontact.com/letters/images/1101116784221/S.gif" style="display:block;height:1px;width:5px" class="CToWUd" data-bit="iit">
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
                        <table class="layout-margin"  width="100%" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td class="layout-margin_cell" style="padding: 0px 40px;" align="center" valign="top">
                              <table class="layout layout--feature layout--3-column" style="table-layout: fixed; background-=color: #ffffff;"  width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
                                <tr>
                                  <td class="column column--1 scale stack" style="width: 26.666666666666668%;"  align="center" valign="top">
                                    <table class="image image--padding-vertical image--mobile-scale image--mobile-center" width="100%" border="0" cellpadding="0" cellspacing="0">
                                      <tr>
                                        <td class="image_container" align="left" valign="top" style="padding-top: 10px; padding-bottom: 10px;">
                                          <img width="57" src="https://ci4.googleusercontent.com/proxy/XTJRnLcGEN8vcECltITxUb87f469mKhli-sgrkRAe5vwV5R8_pCDInobTFomC9a24LMgxyLCHtl-yjIDn27LICPhEgNgTH20RGXR-b9Wn0JnFnbVFs6lTA34A49a5Uz0JtG7jRRw3EN5=s0-d-e1-ft#https://files.constantcontact.com/db08bd92701/05cdd7b8-409b-4fa9-9b1b-9082d1037230.png" alt="" style="display:block;height:auto;max-width:100%" class="CToWUd" data-bit="iit">
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                  <td class="column column--2 scale stack" style="width: 48.33333333333333%;"  align="center" valign="top">
                                    <table class="text text--feature text--padding-vertical"  width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                                      <tr>
                                        <td class="text_content-cell=content-padding-horizontal" style="line-height:1; text-align: left; font-family:Roboto,sans-serif; color: #4d4d4d; font-size: 14px; display:block; word-wrap:break-word; padding: 10px;" align="left" valign="top">
                                          <p style="text-align: center; margin: 0;" align="center">
                                            <br>
                                          </p>
                                          <p style="text-align: center; margin:0;" align="center">
                                            <span style="font-size:12px; font-style: italic;">ACCESS is supported by the</span>
                                          </p>
                                          <p style="text-align:center; margin: 0;" align="center">
                                            <span style="font-size:12px; font-style: italic;">
                                              <span class="ql-cursor">&#xfeff;</span> National Science Foundation. </span>
                                          </p>
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                  <td class="column column--3 scale stack" style="width: 25.000000000000007%;"  align="center" valign="top">
                                    <div class="spacer" style="line-height: 11px; height: 11px;">&#x200a;</div>
                                    <table class="socialFollow socialFollow--padding-vertical" width="100%" cellpadding="0" cellspacing="0" border="0">
                                      <tr>
                                        <td width="100%" align="center" valign="top" style="padding-top:10px;padding-bottom:10px;height:1px;line-height:1px">
                                          <a href="https://www.facebook.com/ACCESSforCI" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://www.facebook.com/ACCESSforCI&amp;source=gmail&amp;ust=1676144118866000&amp;usg=AOvVaw2a_IM9xW6sUzNUzjzt0A8Q">
                                            <img alt="Facebook" width="32" border="0" src="https://ci4.googleusercontent.com/proxy/HTcbG0BDzlzD0DQKJANALNz3Vn16NQjwroYvvNz26oC0PrmKyQ0uVWwIp_JvfkhAnOoVrMqgwLGth8yKiCcyf-MJg9JIC2bjdg6X0x8g2tTehjahFI1mePxU5BC56r3qwlKPgbI_BRrH5Ej1zLMM92Kb-6mjOJGm=s0-d-e1-ft#https://imgssl.constantcontact.com/letters/images/CPE/SocialIcons/circles/circleWhite_Facebook_v4.png" style="display:inline-block;margin:0px;padding:0px" class="CToWUd" data-bit="iit">
                                          </a>
                                          <span>&nbsp;</span>&nbsp; <a href="https://twitter.com/ACCESSforCI" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://twitter.com/ACCESSforCI&amp;source=gmail&amp;ust=1676144118866000&amp;usg=AOvVaw1hi9dqnQwSUDV8VDvhgPqX">
                                            <img alt="Twitter" width="32" border="0" src="https://ci5.googleusercontent.com/proxy/ZfcNmeziv71YfR7wivUIknGGYQ9-eShgGhYER3eWs9V9WWfCTKP4PBL2QtUIsHwcbIiRVdnqOPr1oGadYeCnfJ8xKEEc4woFHfNZP7FDQI3D4LDtqunc9SNS3zkrPPerjsJxpmsW9IuS8tMm7okEqS7bZrjDve0=s0-d-e1-ft#https://imgssl.constantcontact.com/letters/images/CPE/SocialIcons/circles/circleWhite_Twitter_v4.png" style="display:inline-block;margin:0px;padding:0px" class="CToWUd" data-bit="iit">
                                          </a>
                                          <span>&nbsp;</span>&nbsp; <a href="https://www.youtube.com/c/ACCESSforCI" style="text-decoration:underline" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://www.youtube.com/c/ACCESSforCI&amp;source=gmail&amp;ust=1676144118866000&amp;usg=AOvVaw10zl53x7EHshHVzJ9eQ2MG">
                                            <img alt="YouTube" width="32" border="0" src="https://ci3.googleusercontent.com/proxy/NidNTOOWfPeiMCn_0zdk1PGUlNj-tY9YYRmAR5uNWDZVrAxhQI_loLg6m7F_ycJFMyGcQ99v82H5od0fwyrHPuIZKIhSLKfbrHtO-zVpKHlWBwTbZi8pKZ7L0e5KBeZ1flKQxhZF18gcUyp3TrKVEJAT-HK7JwI=s0-d-e1-ft#https://imgssl.constantcontact.com/letters/images/CPE/SocialIcons/circles/circleWhite_YouTube_v4.png" style="display:inline-block;margin:0px;padding:0px" class="CToWUd" data-bit="iit">
                                          </a>
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                        <table class="layout layout--1-column" style="table-lay=out: fixed;" width="100%" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td class="column column--1 scale stack" style="width: 100%;" align="center" valign="top">
                              <div class="spacer" style="line-height: 10px; height: 10px;">&#x200a;</div>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </div>
  </body>
  </html>
EMAILTEXT;
    // note: EMAILTEXT must be to the left column-wise of the last tag (php)
    return $emailText;
}