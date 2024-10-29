/*
    -------------------------------------------------------------------------
    JavaScript Form Validator (gen_validatorv4.js)
    Version 4.0
    Copyright (C) 2003-2011 JavaScript-Coder.com. All rights reserved.
    You can freely use this script in your Web pages.
    You may adapt this script for your own needs, provided these opening credit
    lines are kept intact.
        
    The Form validation script is distributed free from JavaScript-Coder.com
    For updates, please visit:
    http://www.javascript-coder.com/html-form/javascript-form-validation.phtml

    Questions & comments please send to form.val (at) javascript-coder.com
    -------------------------------------------------------------------------  
*/
function Validator(frmname)
{
    this.validate_on_killfocus = false;
    this.formobj = document.forms[frmname];
    if (!this.formobj)
    {
        alert("Error: could not get Form object " + frmname);
        return;
    }
    if (this.formobj.onsubmit)
    {
        this.formobj.old_onsubmit = this.formobj.onsubmit;
        this.formobj.onsubmit = null;
    }
    else
    {
        this.formobj.old_onsubmit = null;
    }
    this.formobj._sfm_form_name = frmname;

    this.formobj.onsubmit = form_submit_handler;
    this.addValidation = add_validation;

    this.formobj.addnlvalidations = [];
    this.addAddnlValidationFunction = add_addnl_vfunction;
    this.formobj.runAddnlValidations = run_addnl_validations;
    this.setAddnlValidationFunction = set_addnl_vfunction;//for backward compatibility


    this.clearAllValidations = clear_all_validations;
    this.focus_disable_validations = false;

    document.error_disp_handler = new Sfm_ErrorDisplayHandler();

    this.EnableOnPageErrorDisplay = validator_enable_OPED;
    this.EnableOnPageErrorDisplaySingleBox = validator_enable_OPED_SB;

    this.show_errors_together = false;
    this.EnableMsgsTogether = sfm_enable_show_msgs_together;
    document.set_focus_onerror = true;
    this.EnableFocusOnError = sfm_validator_enable_focus;

    this.formobj.error_display_loc = 'right';
    this.SetMessageDisplayPos = sfm_validator_message_disp_pos;

    this.formobj.DisableValidations = sfm_disable_validations;
    this.formobj.validatorobj = this;
}


function sfm_validator_enable_focus(enable)
{
    document.set_focus_onerror = enable;
}

function add_addnl_vfunction()
{
    let proc =
        {
        };
    proc.func = arguments[0];
    proc.arguments = [];

    for (let i = 1; i < arguments.length; i++)
    {
        proc.arguments.push(arguments[i]);
    }
    this.formobj.addnlvalidations.push(proc);
}

function set_addnl_vfunction(functionname)
{
    if(functionname.constructor === String)
    {
        alert("Pass the function name like this: validator.setAddnlValidationFunction(DoCustomValidation)\n "+
            "rather than passing the function name as string");
        return;
    }
    this.addAddnlValidationFunction(functionname);
}

function run_addnl_validations()
{
    let ret = true;
    for (let f = 0; f < this.addnlvalidations.length; f++)
    {
        let proc = this.addnlvalidations[f];
        let args = proc.arguments || [];
        if (!proc.func.apply(null, args))
        {
            ret = false;
        }
    }
    return ret;
}

function sfm_set_focus(objInput)
{
    if (document.set_focus_onerror)
    {
        if (!objInput.disabled && objInput.type !== 'hidden')
        {
            objInput.focus();
        }
    }
}

function sfm_disable_validations()
{
    if (this.old_onsubmit)
    {
        this.onsubmit = this.old_onsubmit;
    }
    else
    {
        this.onsubmit = null;
    }
}

function sfm_enable_show_msgs_together()
{
    this.show_errors_together = true;
    this.formobj.show_errors_together = true;
}

function sfm_validator_message_disp_pos(pos)
{
    this.formobj.error_display_loc = pos;
}

function clear_all_validations()
{
    for (let itr = 0; itr < this.formobj.elements.length; itr++)
    {
        this.formobj.elements[itr].validationset = null;
    }
}

function form_submit_handler(e)
{
    e.preventDefault();
    let bRet = true;

    document.error_disp_handler.clear_msgs();
    for (let itr = 0; itr < this.elements.length; itr++)
    {
        if (this.elements[itr].validationset && !this.elements[itr].validationset.validate())
        {
            bRet = false;
        }
        if (!bRet && !this.show_errors_together)
        {
            break;
        }
    }

    if (this.show_errors_together || bRet && !this.show_errors_together)
    {
        if (!this.runAddnlValidations())
        {
            bRet = false;
        }
    }
    if (!bRet)
    {
        document.error_disp_handler.FinalShowMsg();
        return false;
    }

    //added event to hook actions after form validation instead of submition
    window.dispatchEvent(formValidatedEvent);

    return false;
}

function add_validation(itemname, descriptor, errstr)
{
    let condition = null;
    if (arguments.length > 3)
    {
        condition = arguments[3];
    }
    if (!this.formobj)
    {
        alert("Error: The form object is not set properly");
        return;
    } //if
    let itemobj = this.formobj[itemname];

    if (itemobj.length && isNaN(itemobj.selectedIndex))
    //for radio button; don't do for 'select' item
    {
        itemobj = itemobj[0];
    }
    if (!itemobj)
    {
        alert("Error: Couldnot get the input object named: " + itemname);
        return;
    }
    if (true === this.validate_on_killfocus)
    {
        itemobj.onblur = handle_item_on_killfocus;
    }
    if (!itemobj.validationset)
    {
        itemobj.validationset = new ValidationSet(itemobj, this.show_errors_together);
    }
    itemobj.validationset.add(descriptor, errstr, condition);
    itemobj.validatorobj = this;
}

function handle_item_on_killfocus()
{
    if (this.validatorobj.focus_disable_validations === true)
    {
        /*
        To avoid repeated looping message boxes
        */
        this.validatorobj.focus_disable_validations = false;
        return false;
    }

    if (null != this.validationset)
    {
        document.error_disp_handler.clear_msgs();
        if (false === this.validationset.validate())
        {
            document.error_disp_handler.FinalShowMsg();
            return false;
        }
    }
}

function validator_enable_OPED()
{
    document.error_disp_handler.EnableOnPageDisplay(false);
}

function validator_enable_OPED_SB()
{
    document.error_disp_handler.EnableOnPageDisplay(true);
}

function Sfm_ErrorDisplayHandler()
{
    this.msgdisplay = new AlertMsgDisplayer();
    this.EnableOnPageDisplay = edh_EnableOnPageDisplay;
    this.ShowMsg = edh_ShowMsg;
    this.FinalShowMsg = edh_FinalShowMsg;
    this.all_msgs = [];
    this.clear_msgs = edh_clear_msgs;
}

function edh_clear_msgs()
{
    this.msgdisplay.clearmsg(this.all_msgs);
    this.all_msgs = [];
}

function edh_FinalShowMsg()
{
    if (this.all_msgs.length === 0)
    {
        return;
    }
    this.msgdisplay.showmsg(this.all_msgs);
}

function edh_EnableOnPageDisplay(single_box)
{
    if (true === single_box)
    {
        this.msgdisplay = new SingleBoxErrorDisplay();
    }
    else
    {
        this.msgdisplay = new DivMsgDisplayer();
    }
}

function edh_ShowMsg(msg, input_element)
{
    let objmsg = [];
    objmsg["input_element"] = input_element;
    objmsg["msg"] = msg;
    this.all_msgs.push(objmsg);
}

function AlertMsgDisplayer()
{
    this.showmsg = alert_showmsg;
    this.clearmsg = alert_clearmsg;
}

function alert_clearmsg(msgs)
{

}

function alert_showmsg(msgs)
{
    let whole_msg = "";
    let first_elmnt = null;
    for (let m = 0; m < msgs.length; m++)
    {
        if (null == first_elmnt)
        {
            first_elmnt = msgs[m]["input_element"];
        }
        whole_msg += msgs[m]["msg"] + "\n";
    }

    alert(whole_msg);

    if (null != first_elmnt)
    {
        sfm_set_focus(first_elmnt);
    }
}

function sfm_show_error_msg(msg, input_elmt)
{
    document.error_disp_handler.ShowMsg(msg, input_elmt);
}

function SingleBoxErrorDisplay()
{
    this.showmsg = sb_div_showmsg;
    this.clearmsg = sb_div_clearmsg;
}

function sb_div_clearmsg(msgs)
{
    let divname = form_error_div_name(msgs);
    sfm_show_div_msg(divname, "");
}

function sb_div_showmsg(msgs)
{
    let whole_msg = "<ul>\n";
    for (let m = 0; m < msgs.length; m++)
    {
        whole_msg += "<li>" + msgs[m]["msg"] + "</li>\n";
    }
    whole_msg += "</ul>";
    let divname = form_error_div_name(msgs);
    let anc_name = divname + "_loc";
    whole_msg = "<a name='" + anc_name + "' >" + whole_msg;

    sfm_show_div_msg(divname, whole_msg);

    window.location.hash = anc_name;
}

function form_error_div_name(msgs)
{
    let input_element;

    for (let m in msgs)
    {
        if (!msgs.hasOwnProperty(m)) continue;
        input_element = msgs[m]["input_element"];
        if (input_element)
        {
            break;
        }
    }

    let divname = "";
    if (input_element)
    {
        divname = input_element.form._sfm_form_name + "_errorloc";
    }

    return divname;
}

function sfm_show_div_msg(divname,msgstring)
{
    if(divname.length<=0) return false;

    if(document.layers)
    {
        divlayer = document.layers[divname];
        if(!divlayer){return;}
        divlayer.document.open();
        divlayer.document.write(msgstring);
        divlayer.document.close();
    }
    else
    if(document.all)
    {
        divlayer = document.all[divname];
        if(!divlayer){return;}
        divlayer.innerHTML=msgstring;
    }
    else
    if(document.getElementById)
    {
        divlayer = document.getElementById(divname);
        if(!divlayer){return;}
        divlayer.innerHTML =msgstring;
    }
    divlayer.style.visibility="visible";
    return false;
}

function DivMsgDisplayer()
{
    this.showmsg = div_showmsg;
    this.clearmsg = div_clearmsg;
}

function div_clearmsg(msgs)
{
    for (let m in msgs)
    {
        if (!msgs.hasOwnProperty(m)) continue;
        let divname = element_div_name(msgs[m]["input_element"]);
        show_div_msg(divname, "");
    }
}

function element_div_name(input_element)
{
    let divname = input_element.form._sfm_form_name + "_" + input_element.name + "_errorloc";

    divname = divname.replace(/[\[\]]/gi, "");

    return divname;
}

function div_showmsg(msgs)
{
    let first_elmnt = null;
    for (let m in msgs)
    {
        if (!msgs.hasOwnProperty(m)) continue;
        if (null == first_elmnt)
        {
            first_elmnt = msgs[m]["input_element"];
        }
        let divname = element_div_name(msgs[m]["input_element"]);
        show_div_msg(divname, msgs[m]["msg"]);
    }
    if (null != first_elmnt)
    {
        sfm_set_focus(first_elmnt);
    }
}

function show_div_msg(divname, msgstring)
{
    if (divname.length <= 0) return false;

    if (document.layers)
    {
        divlayer = document.layers[divname];
        if (!divlayer)
        {
            return;
        }
        divlayer.document.open();
        divlayer.document.write(msgstring);
        divlayer.document.close();
    }
    else if (document.all)
    {
        divlayer = document.all[divname];
        if (!divlayer)
        {
            return;
        }
        divlayer.innerHTML = msgstring;
    }
    else if (document.getElementById)
    {
        divlayer = document.getElementById(divname);
        if (!divlayer)
        {
            return;
        }
        divlayer.innerHTML = msgstring;
    }
    divlayer.style.visibility = "visible";
}

function ValidationDesc(inputitem, desc, error, condition)
{
    this.desc = desc;
    this.error = error;
    this.itemobj = inputitem;
    this.condition = condition;
    this.validate = vdesc_validate;
}

function vdesc_validate()
{
    if (this.condition != null)
    {
        if (!eval(this.condition))
        {
            return true;
        }
    }
    if (!validateInput(this.desc, this.itemobj, this.error))
    {
        this.itemobj.validatorobj.focus_disable_validations = true;
        sfm_set_focus(this.itemobj);
        return false;
    }

    return true;
}

function ValidationSet(inputitem, msgs_together)
{
    this.vSet = [];
    this.add = add_validationdesc;
    this.validate = vset_validate;
    this.itemobj = inputitem;
    this.msgs_together = msgs_together;
}

function add_validationdesc(desc, error, condition)
{
    this.vSet[this.vSet.length] =
        new ValidationDesc(this.itemobj, desc, error, condition);
}

function vset_validate()
{
    let bRet = true;
    for (let itr = 0; itr < this.vSet.length; itr++)
    {
        bRet = bRet && this.vSet[itr].validate();
        if (!bRet && !this.msgs_together)
        {
            break;
        }
    }
    return bRet;
}

/*  checks the validity of an email address entered
*   returns true or false
*/
function validateEmail(email)
{
    let splitted = email.match("^(.+)@(.+)$");
    if (splitted == null) return false;
    if (splitted[1] != null)
    {
        let regexp_user = /^\"?[\w-_\.]*\"?$/;
        if (splitted[1].match(regexp_user) == null) return false;
    }
    if (splitted[2] != null)
    {
        let regexp_domain = /^[\w-\.]*\.[A-Za-z]{2,4}$/;
        if (splitted[2].match(regexp_domain) == null)
        {
            let regexp_ip = /^\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\]$/;
            if (splitted[2].match(regexp_ip) == null) return false;
        } // if
        return true;
    }
    return false;
}

function testComparison(objValue, strCompareElement, strvalidator, strError)
{
    let bRet = true;
    let objCompare;
    if (!objValue.form)
    {
        sfm_show_error_msg("Error: No Form object!", objValue);
        return false;
    }
    objCompare = objValue.form.elements[strCompareElement];
    if (!objCompare)
    {
        sfm_show_error_msg("Error: Element with name" + strCompareElement + " not found !", objValue);
        return false;
    }

    let objval_value = objValue.value;
    let objcomp_value = objCompare.value;

    if (strvalidator !== "eqelmnt" && strvalidator !== "neelmnt")
    {
        objval_value = objval_value.replace(/\,/g, "");
        objcomp_value = objcomp_value.replace(/\,/g, "");

        if (isNaN(objval_value))
        {
            sfm_show_error_msg(objValue.name + ": Should be a number ", objValue);
            return false;
        } //if
        if (isNaN(objcomp_value))
        {
            sfm_show_error_msg(objCompare.name + ": Should be a number ", objCompare);
            return false;
        } //if
    } //if
    let cmpstr = "";
    switch (strvalidator)
    {
        case "eqelmnt":
        {
            if (objval_value !== objcomp_value)
            {
                cmpstr = " should be equal to ";
                bRet = false;
            } //if
            break;
        } //case
        case "ltelmnt":
        {
            if (eval(objval_value) >= eval(objcomp_value))
            {
                cmpstr = " should be less than ";
                bRet = false;
            }
            break;
        } //case
        case "leelmnt":
        {
            if (eval(objval_value) > eval(objcomp_value))
            {
                cmpstr = " should be less than or equal to";
                bRet = false;
            }
            break;
        } //case
        case "gtelmnt":
        {
            if (eval(objval_value) <= eval(objcomp_value))
            {
                cmpstr = " should be greater than";
                bRet = false;
            }
            break;
        } //case
        case "geelmnt":
        {
            if (eval(objval_value) < eval(objcomp_value))
            {
                cmpstr = " should be greater than or equal to";
                bRet = false;
            }
            break;
        } //case
        case "neelmnt":
        {
            if (objval_value.length > 0 && objcomp_value.length > 0 && objval_value === objcomp_value)
            {
                cmpstr = " should be different from ";
                bRet = false;
            } //if
            break;
        }
    } //switch
    if (bRet === false)
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + cmpstr + objCompare.name;
        } //if
        sfm_show_error_msg(strError, objValue);
    } //if
    return bRet;
}

function testSelMin(objValue, strMinSel, strError)
{
    let bret = true;
    let objcheck = objValue.form.elements[objValue.name];
    let chkcount = 0;
    if (objcheck.length)
    {
        for (let c = 0; c < objcheck.length; c++)
        {
            if (objcheck[c].checked === "1")
            {
                chkcount++;
            } //if
        } //for
    }
    else
    {
        chkcount = (objcheck.checked === "1") ? 1 : 0;
    }
    let minsel = eval(strMinSel);
    if (chkcount < minsel)
    {
        if (!strError || strError.length === 0)
        {
            strError = "Please Select atleast" + minsel + " check boxes for" + objValue.name;
        } //if
        sfm_show_error_msg(strError, objValue);
        bret = false;
    }
    return bret;
}

function testSelMax(objValue, strMaxSel, strError)
{
    let bret = true;
    let objcheck = objValue.form.elements[objValue.name];
    let chkcount = 0;
    if (objcheck.length)
    {
        for (let c = 0; c < objcheck.length; c++)
        {
            if (objcheck[c].checked === "1")
            {
                chkcount++;
            } //if
        } //for
    }
    else
    {
        chkcount = (objcheck.checked === "1") ? 1 : 0;
    }
    let maxsel = eval(strMaxSel);
    if (chkcount > maxsel)
    {
        if (!strError || strError.length === 0)
        {
            strError = "Please Select atmost " + maxsel + " check boxes for" + objValue.name;
        } //if
        sfm_show_error_msg(strError, objValue);
        bret = false;
    }
    return bret;
}

function isCheckSelected(objValue, chkValue)
{
    let selected = false;
    let objcheck = objValue.form.elements[objValue.name];
    if (objcheck.length)
    {
        let idxchk = -1;
        for (let c = 0; c < objcheck.length; c++)
        {
            if (objcheck[c].value === chkValue)
            {
                idxchk = c;
                break;
            } //if
        } //for
        if (idxchk >= 0)
        {
            if (objcheck[idxchk].checked === "1")
            {
                selected = true;
            }
        } //if
    }
    else
    {
        if (objValue.checked === "1")
        {
            selected = true;
        } //if
    } //else
    return selected;
}

function testDontSelectChk(objValue, chkValue, strError)
{
    let pass;
    pass = !isCheckSelected(objValue, chkValue);

    if (pass === false)
    {
        if (!strError || strError.length === 0)
        {
            strError = "Can't Proceed as you selected " + objValue.name;
        } //if
        sfm_show_error_msg(strError, objValue);

    }
    return pass;
}

function testShouldSelectChk(objValue, chkValue, strError)
{
    let pass;

    pass = isCheckSelected(objValue, chkValue);

    if (pass === false)
    {
        if (!strError || strError.length === 0)
        {
            strError = "You should select" + objValue.name;
        } //if
        sfm_show_error_msg(strError, objValue);

    }
    return pass;
}

function testRequiredInput(objValue, strError)
{
    let ret = true;
    if (vwz_IsEmpty(objValue.value))
    {
        ret = false;
    } //if
    else if (objValue.getcal && !objValue.getcal())
    {
        ret = false;
    }

    if (!ret)
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + " : Required Field";
        } //if
        sfm_show_error_msg(strError, objValue);
    }
    return ret;
}

function testFileExtension(objValue, cmdvalue, strError)
{
    let ret = false;
    let found = false;

    if (objValue.value.length <= 0)
    { //The 'required' validation is not done here
        return true;
    }

    let extns = cmdvalue.split(";");
    for (let i = 0; i < extns.length; i++)
    {
        ext = objValue.value.substr(objValue.value.length - extns[i].length, extns[i].length);
        ext = ext.toLowerCase();
        if (ext === extns[i])
        {
            found = true;
            break;
        }
    }
    if (!found)
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + " allowed file extensions are: " + cmdvalue;
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    }
    else
    {
        ret = true;
    }
    return ret;
}

function testMaxLen(objValue, strMaxLen, strError)
{
    let ret = true;
    if (eval(objValue.value.length) > eval(strMaxLen))
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + " : " + strMaxLen + " characters maximum ";
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    } //if
    return ret;
}

function testMinLen(objValue, strMinLen, strError)
{
    let ret = true;
    if (eval(objValue.value.length) < eval(strMinLen))
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + " : " + strMinLen + " characters minimum  ";
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    } //if
    return ret;
}

function testInputType(objValue, strRegExp, strError, strDefaultError)
{
    let ret = true;

    let charpos = objValue.value.search(strRegExp);
    if (objValue.value.length > 0 && charpos >= 0)
    {
        if (!strError || strError.length === 0)
        {
            strError = strDefaultError;
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    } //if
    return ret;
}

function testEmail(objValue, strError)
{
    let ret = true;
    if (objValue.value.length > 0 && !validateEmail(objValue.value))
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + ": Enter a valid Email address ";
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    } //if
    return ret;
}

function testLessThan(objValue, strLessThan, strError)
{
    let ret = true;
    let obj_value = objValue.value.replace(/\,/g, "");
    strLessThan = strLessThan.replace(/\,/g, "");

    if (isNaN(obj_value))
    {
        sfm_show_error_msg(objValue.name + ": Should be a number ", objValue);
        ret = false;
    } //if
    else if (eval(obj_value) >= eval(strLessThan))
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + " : value should be less than " + strLessThan;
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    } //if
    return ret;
}

function testGreaterThan(objValue, strGreaterThan, strError)
{
    let ret = true;
    let obj_value = objValue.value.replace(/\,/g, "");
    strGreaterThan = strGreaterThan.replace(/\,/g, "");

    if (isNaN(obj_value))
    {
        sfm_show_error_msg(objValue.name + ": Should be a number ", objValue);
        ret = false;
    } //if
    else if (eval(obj_value) <= eval(strGreaterThan))
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + " : value should be greater than " + strGreaterThan;
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    } //if
    return ret;
}

function testRegExp(objValue, strRegExp, strError)
{
    let ret = true;
    if (objValue.value.length > 0 && !objValue.value.match(strRegExp))
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + ": Invalid characters found ";
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    } //if
    return ret;
}

function testDontSelect(objValue, dont_sel_value, strError)
{
    let ret = true;
    if (objValue.value == null)
    {
        sfm_show_error_msg("Error: dontselect command for non-select Item", objValue);
        ret = false;
    }
    else if (objValue.value === dont_sel_value)
    {
        if (!strError || strError.length === 0)
        {
            strError = objValue.name + ": Please Select one option ";
        } //if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    }
    return ret;
}

function testSelectOneRadio(objValue, strError)
{
    let objradio = objValue.form.elements[objValue.name];
    let one_selected = false;
    for (let r = 0; r < objradio.length; r++)
    {
        if (objradio[r].checked === "1")
        {
            one_selected = true;
            break;
        }
    }
    if (false === one_selected)
    {
        if (!strError || strError.length === 0)
        {
            strError = "Please select one option from " + objValue.name;
        }
        sfm_show_error_msg(strError, objValue);
    }
    return one_selected;
}

function testSelectRadio(objValue, cmdvalue, strError, testselect)
{
    let objradio = objValue.form.elements[objValue.name];
    let selected = false;

    for (let r = 0; r < objradio.length; r++)
    {
        if (objradio[r].value === cmdvalue && objradio[r].checked === "1")
        {
            selected = true;
            break;
        }
    }
    if (testselect === true && false === selected || testselect === false && true === selected)
    {
        sfm_show_error_msg(strError, objValue);
        return false;
    }
    return true;
}


//*  Checks each field in a form


function validateInput(strValidateStr, objValue, strError)
{

    let ret = true;
    let epos = strValidateStr.search("=");
    let command = "";
    let cmdvalue = "";
    if (epos >= 0)
    {
        command = strValidateStr.substring(0, epos);
        cmdvalue = strValidateStr.substr(epos + 1);
    }
    else
    {
        command = strValidateStr;
    }

    switch (command)
    {
        case "req":
        case "required":
        {
            ret = testRequiredInput(objValue, strError);
            break;
        }
        case "maxlength":
        case "maxlen":
        {
            ret = testMaxLen(objValue, cmdvalue, strError);
            break;
        }
        case "minlength":
        case "minlen":
        {
            ret = testMinLen(objValue, cmdvalue, strError);
            break;
        }
        case "alnum":
        case "alphanumeric":
        {
            ret = testInputType(objValue, "[^A-Za-z0-9]", strError, objValue.name + ": Only alpha-numeric characters allowed ");
            break;
        }
        case "alnum_s":
        case "alphanumeric_space":
        {
            ret = testInputType(objValue, "[^A-Za-z0-9\\s]", strError, objValue.name + ": Only alpha-numeric characters and space allowed ");
            break;
        }
        case "num":
        case "numeric":
        case "dec":
        case "decimal":
        {
            if (objValue.value.length > 0 && !objValue.value.match(/^[\-\+]?[\d\,]*\.?[\d]*$/))
            {
                sfm_show_error_msg(strError, objValue);
                ret = false;
            } //if
            break;
        }
        case "alphabetic":
        case "alpha":
        {
            ret = testInputType(objValue, "[^A-Za-z]", strError, objValue.name + ": Only alphabetic characters allowed ");
            break;
        }
        case "alphabetic_space":
        case "alpha_s":
        {
            ret = testInputType(objValue, "[^A-Za-z\\s]", strError, objValue.name + ": Only alphabetic characters and space allowed ");
            break;
        }
        case "email":
        {
            ret = testEmail(objValue, strError);
            break;
        }
        case "lt":
        case "lessthan":
        {
            ret = testLessThan(objValue, cmdvalue, strError);
            break;
        }
        case "gt":
        case "greaterthan":
        {
            ret = testGreaterThan(objValue, cmdvalue, strError);
            break;
        }
        case "regexp":
        {
            ret = testRegExp(objValue, cmdvalue, strError);
            break;
        }
        case "dontselect":
        {
            ret = testDontSelect(objValue, cmdvalue, strError);
            break;
        }
        case "dontselectchk":
        {
            ret = testDontSelectChk(objValue, cmdvalue, strError);
            break;
        }
        case "shouldselchk":
        {
            ret = testShouldSelectChk(objValue, cmdvalue, strError);
            break;
        }
        case "selmin":
        {
            ret = testSelMin(objValue, cmdvalue, strError);
            break;
        }
        case "selmax":
        {
            ret = testSelMax(objValue, cmdvalue, strError);
            break;
        }
        case "selone_radio":
        case "selone":
        {
            ret = testSelectOneRadio(objValue, strError);
            break;
        }
        case "dontselectradio":
        {
            ret = testSelectRadio(objValue, cmdvalue, strError, false);
            break;
        }
        case "selectradio":
        {
            ret = testSelectRadio(objValue, cmdvalue, strError, true);
            break;
        }
        //Comparisons
        case "eqelmnt":
        case "ltelmnt":
        case "leelmnt":
        case "gtelmnt":
        case "geelmnt":
        case "neelmnt":
        {
            return testComparison(objValue, cmdvalue, command, strError);
        }
        case "req_file":
        {
            ret = testRequiredInput(objValue, strError);
            break;
        }
        case "file_extn":
        {
            ret = testFileExtension(objValue, cmdvalue, strError);
            break;
        }

    } //switch
    return ret;
}

function vwz_IsListItemSelected(listname, value)
{
    for (let i = 0; i < listname.options.length; i++)
    {
        if (listname.options[i].selected === true && listname.options[i].value === value)
        {
            return true;
        }
    }
    return false;
}

function vwz_IsChecked(objcheck, value)
{
    if (objcheck.length)
    {
        for (let c = 0; c < objcheck.length; c++)
        {
            if (objcheck[c].checked === "1" && objcheck[c].value === value)
            {
                return true;
            }
        }
    }
    else
    {
        if (objcheck.checked === "1")
        {
            return true;
        }
    }
    return false;
}

function sfm_str_trim(strIn)
{
    return strIn.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
}

function vwz_IsEmpty(value)
{
    value = sfm_str_trim(value);
    return (value.length) === 0;
}

/*
	Copyright (C) 2003-2011 JavaScript-Coder.com . All rights reserved.
*/