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

function ccNewsRollupHTML($news, $events)
{
    $newsBody = '<div class="access-news-rollup-email">'
                  . '<div class="access-news-rollup-news">' . $news . '</div>'
                  . '<hr>'
                  . '<div class="access-news-rollup-events">' . $events . '</div>'
                  . '</div>';

    return ccNewsCommonHTML($newsBody, '', '', '', '');
}
/**
 * Undocumented function
 */
function ccNewsSingleHTML($newsBody, $newsTitle, $pubDate, $agNames, $newsUrl)
{
    // Build list of one or more affinity group names separated by 'or'.
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
    PUBDATE;
    }
    return ccNewsCommonHTML($newsBody, $newsTitle, $pubDateDisplay, $agText, $newsUrl);
}

/**
 *
 */

function ccNewsCommonHTML($newsBody, $newsTitle, $pubDateDisplay, $agNameDisplay, $newsUrl)
{
     // HTML with values for newsBody, newsTitle, pubdate and agText inserted.
    $emailText = <<<EMAILTEXT
<html>
<body>[[trackingImage]]
<div>
  <div lang="en-US" style="font-family:Helvetica;font-size:12px;font-style:normal;font-variant-caps:normal;font-weight:400;letter-spacing:normal;text-align:center;text-indent:0px;text-transform:none;white-space:normal;word-spacing:0px;text-decoration:none;background-color:rgb(26,91,110)">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#1a5b6e" style="table-layout:fixed;background-color:rgb(26,91,110)">
      <tbody>
        <tr>
          <td align="center" valign="top">
            <table align="center" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;width:700px">
              <tbody>
                <tr>
                  <td align="center" valign="top" style="padding:15px 10px">
                    <table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed">
                      <tbody>
                        <tr>
                          <td align="center" valign="top" bgcolor="#FFFFFF" style="background-color:rgb(255,255,255);padding:0px;border:0px solid rgb(62,62,62)">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width:100%">
                              <tbody>
                                <tr>
                                  <td align="center" valign="top" style="width:680px">
                                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed">
                                      <tbody>
                                        <tr>
                                          <td align="center" valign="top">
                                            <img width="680" src="https://ci4.googleusercontent.com/proxy/xcHw8mUnG_iWFWR9dul6oQMRuNJ9VGNEfZpSoOS4L2XQgZJuTT-cHwdXG-8XNBo8hUmkN_lF3dom96zj9NV1yjZ9e_T_PvmNI5s9pr2ARCURoNfSg-DhKLSXeHo_dWKnDCKYmg-l-JoX=s0-d-e1-ft#https://files.constantcontact.com/db08bd92701/8e5fb40a-35cc-4141-8f25-37caaed9ad80.jpg" alt="" style="display:block;height:auto;max-width:100%" class="CToWUd a6T" data-bit="iit" tabindex="0">
                                            <div class="a6S" dir="ltr" style="opacity: 0.01; left: 682px; top: 353.884px;">
                                              <div id=":x5" class="T-I J-J5-Ji aQv T-I-ax7 L3 a5q" role="button" tabindex="0" aria-label="Download attachment " jslog="91252; u014N:cOuCgd,Kr2w4b,xr6bB" data-tooltip-class="a1V" data-tooltip="Download">
                                                <div class="akn">
                                                  <div class="aSK J-J5-Ji aYr"></div>
                                                </div>
                                              </div>
                                            </div>
                                          </td>
                                        </tr>
                                      </tbody>
                                    </table>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width:100%">
                              <tbody>
                                <tr>
                                  <td align="center" valign="top" style="width:680px">
                                    <div style="line-height:13px;height:13px"></div>
                                  </td>
                                </tr>
                              </tbody>
                            </table>

                            <div>$newsBody</div>

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




                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed">
                              <tbody>
                                <tr>
                                  <td align="center" valign="top" style="padding:0px 40px">
                                    <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="table-layout:fixed;min-width:100%;background-color:rgb(255,255,255)">
                                      <tbody>
                                        <tr>
                                          <td align="center" valign="top" style="width:160px">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed">
                                              <tbody>
                                                <tr>
                                                  <td align="left" valign="top" style="padding-top:10px;padding-bottom:10px">
                                                    <img width="57" src="https://ci4.googleusercontent.com/proxy/XTJRnLcGEN8vcECltITxUb87f469mKhli-sgrkRAe5vwV5R8_pCDInobTFomC9a24LMgxyLCHtl-yjIDn27LICPhEgNgTH20RGXR-b9Wn0JnFnbVFs6lTA34A49a5Uz0JtG7jRRw3EN5=s0-d-e1-ft#https://files.constantcontact.com/db08bd92701/05cdd7b8-409b-4fa9-9b1b-9082d1037230.png" alt="" style="display:block;height:auto;max-width:100%" class="CToWUd" data-bit="iit">
                                                  </td>
                                                </tr>
                                              </tbody>
                                            </table>
                                          </td>
                                          <td align="center" valign="top" style="width:290px">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed">
                                              <tbody>
                                                <tr>
                                                  <td align="left" valign="top" style="line-height:1;text-align:left;font-family:Roboto,sans-serif;color:rgb(77,77,77);font-size:14px;display:block;padding:10px">
                                                    <div style="margin:0px;padding:0px;text-align:center">
                                                      <br>
                                                    </div>
                                                    <div style="margin:0px;padding:0px;text-align:center">
                                                      <span style="font-size:12px;font-style:italic">ACCESS is supported by the <span>&nbsp;</span>
                                                      </span>
                                                    </div>
                                                    <div style="margin:0px;padding:0px;text-align:center">
                                                      <span style="font-size:12px;font-style:italic">
                                                        <span></span>National Science Foundation. </span>
                                                    </div>
                                                  </td>
                                                </tr>
                                              </tbody>
                                            </table>
                                          </td>
                                          <td align="center" valign="top" style="width:150px">
                                            <div style="line-height:11px;height:11px"></div>
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="table-layout:fixed">
                                              <tbody>
                                                <tr>
                                                  <td width="100%" align="center" valign="top" style="padding-top:10px;padding-bottom:10px;height:1px;line-height:1px">
                                                    <a href="https://www.facebook.com/ACCESSforCI" style="text-decoration:underline" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://www.facebook.com/ACCESSforCI&amp;source=gmail&amp;ust=1676144118866000&amp;usg=AOvVaw2a_IM9xW6sUzNUzjzt0A8Q">
                                                      <img alt="Facebook" width="32" border="0" src="https://ci4.googleusercontent.com/proxy/HTcbG0BDzlzD0DQKJANALNz3Vn16NQjwroYvvNz26oC0PrmKyQ0uVWwIp_JvfkhAnOoVrMqgwLGth8yKiCcyf-MJg9JIC2bjdg6X0x8g2tTehjahFI1mePxU5BC56r3qwlKPgbI_BRrH5Ej1zLMM92Kb-6mjOJGm=s0-d-e1-ft#https://imgssl.constantcontact.com/letters/images/CPE/SocialIcons/circles/circleWhite_Facebook_v4.png" style="display:inline-block;margin:0px;padding:0px" class="CToWUd" data-bit="iit">
                                                    </a>
                                                    <span>&nbsp;</span>&nbsp; <a href="https://twitter.com/ACCESSforCI" style="text-decoration:underline" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://twitter.com/ACCESSforCI&amp;source=gmail&amp;ust=1676144118866000&amp;usg=AOvVaw1hi9dqnQwSUDV8VDvhgPqX">
                                                      <img alt="Twitter" width="32" border="0" src="https://ci5.googleusercontent.com/proxy/ZfcNmeziv71YfR7wivUIknGGYQ9-eShgGhYER3eWs9V9WWfCTKP4PBL2QtUIsHwcbIiRVdnqOPr1oGadYeCnfJ8xKEEc4woFHfNZP7FDQI3D4LDtqunc9SNS3zkrPPerjsJxpmsW9IuS8tMm7okEqS7bZrjDve0=s0-d-e1-ft#https://imgssl.constantcontact.com/letters/images/CPE/SocialIcons/circles/circleWhite_Twitter_v4.png" style="display:inline-block;margin:0px;padding:0px" class="CToWUd" data-bit="iit">
                                                    </a>
                                                    <span>&nbsp;</span>&nbsp; <a href="https://www.youtube.com/c/ACCESSforCI" style="text-decoration:underline" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://www.youtube.com/c/ACCESSforCI&amp;source=gmail&amp;ust=1676144118866000&amp;usg=AOvVaw10zl53x7EHshHVzJ9eQ2MG">
                                                      <img alt="YouTube" width="32" border="0" src="https://ci3.googleusercontent.com/proxy/NidNTOOWfPeiMCn_0zdk1PGUlNj-tY9YYRmAR5uNWDZVrAxhQI_loLg6m7F_ycJFMyGcQ99v82H5od0fwyrHPuIZKIhSLKfbrHtO-zVpKHlWBwTbZi8pKZ7L0e5KBeZ1flKQxhZF18gcUyp3TrKVEJAT-HK7JwI=s0-d-e1-ft#https://imgssl.constantcontact.com/letters/images/CPE/SocialIcons/circles/circleWhite_YouTube_v4.png" style="display:inline-block;margin:0px;padding:0px" class="CToWUd" data-bit="iit">
                                                    </a>
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
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;min-width:100%">
                              <tbody>
                                <tr>
                                  <td align="center" valign="top" style="width:680px">
                                    <div style="line-height:10px;height:10px"></div>
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
 </body>
</html>
EMAILTEXT;
    // note: EMAILTEXT must be to the left column-wise of the last tag (php)
    return $emailText;
}
