'use strict';
const SCT_BLOCK_TYPES = {
  Text: 0,
  StaticCode: 1,
  SolutionCode: 2,
  HiddenCode: 3,
  Test: 4
};
function _SCTCodeblock(parent, block) {
  if (!(this instanceof _SCTCodeblock)) {
    return new _SCTCodeblock(parent, block);
  }
  const jblock = $(block);
  this.jq = jblock;
  this.editor = null;
  this.add_editor = function() {
    if (this.editor) return;
    var editor = CodeMirror.fromTextArea(block, {
      lineNumbers: true,
      mode: parent.lang,
      readOnly: jblock.attr("data-readonly")!==undefined,
      theme: "default",
      tabSize: 4,
      indentUnit: 4,
      autoCloseBrackets: true,
      autoRefresh: true,
      firstLineNumber: 1,
      gutters: ["diagnostics", "CodeMirror-linenumbers"]
    });
    this.editor = editor;
    if (editor.display.input.textarea) {
      editor.display.input.textarea.className = "noRTEditor";
    }
    editor.on("change", function(cMirror) {
      jblock.val(cMirror.getValue());
    });
    editor.addKeyMap({
      Tab: function(cMirror) {
        cMirror.execCommand("insertSoftTab");
      }
    });
  };
  this.remove_editor = function() {
    if (!this.editor) return;
    this.editor.toTextArea();
    this.editor = null;
  };
  this.resize = function(val = null) {
    if (val) {
      jblock.attr("data-rows", val);
    } else {
      val = jblock.attr("data-rows");
    }
    const height = Math.round(20 * val) + 9;
    if (this.editor) {
      this.editor.setSize(null, height);
      this.editor.refresh();
    }
    jblock.height(height);
  };
  this.switch_language = function() {
    if (!this.editor) return;
    this.editor.setOption("mode", parent.lang);
  };
  this.is_encoded = function() {
    return jblock.attr("data-encoded") !== undefined;
  };
  this.is_ignored = function() {
    return jblock.attr("data-ignored") != undefined;
  };
  this.get_type = function() {
    return jblock.attr("data-blocktype");
  };
  this.set_type = function(val = null) {
    if (val) {
      jblock.attr("data-blocktype", val);
    } else {
      val = this.get_type();
    }
    if (val > 0 && !this.is_encoded()) {
      this.add_editor();
    } else {
      this.remove_editor();
    }
    this.resize();
  };
  this.has_code = function() {
    if (this.is_ignored()) return false;
    return this.get_type() != SCT_BLOCK_TYPES.Text;
  };
  this.get_code = function() {
    if (!this.has_code()) return "";
    const val = jblock.val();
    return (this.is_encoded() ? Base64.decode(val) : val).trim();
  };
  block.sct = this;
  this.set_type();
}

function SCTCodeblock(id, useMode) {
  if (!(this instanceof SCTCodeblock)) {
    return new SCTCodeblock(id, useMode);
  }

  const solution_box = $(`#sct_codeblocks_${id}`);

  this.jq = solution_box;
  this.lang = useMode;
  this.blocks = {};
  this.switch_language = function(lang) {
    this.lang = lang;
    Object.values(this.blocks).forEach(block => {
      block.switch_language();
    });
  };
  this.add_block = function(block) {
    const id = block.getAttribute("data-block-id");
    this.blocks[id] = _SCTCodeblock(this, block);
  };
  this.get_block = function(id) {
    return this.blocks[id];
  };
  this.create_block = function(template) {
    const maxNr = Math.max(-1, ...Object.keys(this.blocks)) + 1;
    template = template.replace(/\[ID\]/g, maxNr);
    const block = $(template);
    solution_box.append(block);
    const editor = block.find("textarea");
    editor.removeAttr("data-ignore");
    this.add_block(editor[0]);
  };
  this.has_test = function() {
    return Object.values(this.blocks).some(
      block => block.get_type() == SCT_BLOCK_TYPES.Test
    );
  };
  this.get_code = function(test = false) {
    if (test && !this.has_test()) return "";
    const ignore_type = test
      ? SCT_BLOCK_TYPES.SolutionCode
      : SCT_BLOCK_TYPES.Test;
    return Object.values(this.blocks)
      .filter(block => block.has_code())
      .filter(block => block.get_type() != ignore_type)
      .map(block => block.get_code())
      .join("\n");
  };

  solution_box.find(`textarea[data-question-id=${id}]`).each((i, block) => {
    if (block.hasAttribute("data-ignore")) return;
    this.add_block(block);
  });

  solution_box[0].sct = this;
}

$(document).ready(function() {
  //we need this for the manual scoring view, otherwise the boxes have to get clicked
  setTimeout(function() {
    $(".CodeMirror").each(function(i, el) {
      el.CodeMirror.refresh();
    });
  }, 400);
});
