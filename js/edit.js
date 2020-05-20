'use strict';

//! ---------------------------------------------------------------------
//! -------------------------- url Selection ----------------------------
//! ---------------------------------------------------------------------
function sct_init_edit() {
  const url = $("#url");
  const token = $("#token");
  const func = $("#func");

  const cb_sct = $(".sct_exec")[0].sct;
  //Todo: validate url and token;
  /* const x = async function() {
    if (!cb_sct.valid()) {
      url.css("background-color", "red");
      token.css("background-color", "");
    }else {
      url.css("background-color", "");
      token.css("background-color", "");
    }
  }*/
  func.on("input", function() {
    cb_sct.func(func.val());
  });
  url.on("input", function() {
    cb_sct.url(url.val());
  });
  token.on("input", function() {
    cb_sct.token(token.val());
  });

}

//! ---------------------------------------------------------------------
//! ------------------------ language Selection -------------------------
//! ---------------------------------------------------------------------

var languages = {};
var current_language = undefined;
function registerLanguages(data) {
  languages = data;
}
function selectLanguage(select) {
  select = $(select);
  const lang = select.val();
  const cb_sct = $(".sct_codeblocks")[0].sct;
  cb_sct.switch_language(languages[lang]);
}

//! ---------------------------------------------------------------------
//! -------------------------- type Selection ---------------------------
//! ---------------------------------------------------------------------

function selectType(select, bid) {
  select = $(select);
  const cb_sct = select.closest(".sct_codeblocks")[0].sct;
  const block = cb_sct.get_block(bid);
  block.set_type(select.val());
}

//! ---------------------------------------------------------------------
//! ------------------------------ sizing -------------------------------
//! ---------------------------------------------------------------------

function resizeBlock(input, bid) {
  input = $(input);
  const cb_sct = input.closest(".sct_codeblocks")[0].sct;
  const block = cb_sct.get_block(bid);
  block.resize(input.val());
}

//! ---------------------------------------------------------------------
//! -------------------------- block creation ---------------------------
//! ---------------------------------------------------------------------
function addBlock(button) {
  button = $(button);

  const tpl = $(`#sct_template`);
  let html = tpl.html();
  const blocks = button.siblings(".sct_codeblocks")[0];
  blocks.sct.create_block(html);
}

function removeBlock(id) {
  var obj = $(id)[0];
  obj.remove();
}
