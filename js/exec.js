'use strict';

function SCTOutput(id) {
  if (!(this instanceof SCTOutput)) {
    return new SCTOutput(id);
  }
  const output = $(`#sct_output_${id}`);
  this.clear = function() {
    output.hide();
  };
  this.set = function(data, error) {
    output.show();
    output.html("");
    var res = "";
    if (error) {
      try {
        if (error.text) {
          res = "<pre>" + escape(error.text) + "</pre>";
        } else {
          res = "<pre>" + escape(error.key) + "</pre>";
        }
      } catch (e) {
        res = "<pre>" + String(e) + "</pre>";
      }
      output.append($(res))
    }
    if (data) {
      try {
        res = "<pre>" + escape(data) + "</pre>";
      } catch (e) {
        res = "<pre>" + String(e) + "</pre>";
      }
      output.append($(res))
    }
  };
}

function SCTState(id) {
  if (!(this instanceof SCTState)) {
    return new SCTState(id);
  }
  this._frame = $(`#sct_state_${id}`);
  this._message = $(`#sct_state_m_${id}`);
  this._dots = $(`#sct_state_d_${id}`);
  this.show = function(message, dots = true) {
    this._frame.show().css("display", "inline-block");
    if (dots) {
      this._dots.show();
    } else {
      this._dots.hide();
    }
    this._message.html(message);
    this._frame.show();
  };
  this.hide = function() {
    this._frame.hide();
  };
}
function SCTPoints(id) {
  if (!(this instanceof SCTPoints)) {
    return new SCTPoints(id);
  }
  this._frame = $(`#sct_points_${id}`);
  this.set = function(value=0) {
    this._frame.val(value);
  };
}

function SCTExec(id, url, token) {
  if (!(this instanceof SCTExec)) {
    return new SCTExec(id, url, token);
  }
  this.state = SCTState(id);
  this.output = SCTOutput(id);
  this.points = SCTPoints(id);

  const exec_box = $(`#sct_exec_${id}`);
  const codebox = exec_box.find(".sct_codeblocks")[0].sct;
  this._url = null;
  this._token = null;
  this.url = function(url = undefined) {
    if (url === undefined) {
      return this._url;
    }
    url = url.trim().replace(/\/$/, "");
    this._url = url === "" ? undefined : url;
  };
  this.token = function(token = undefined) {
    if (token === undefined) {
      return this._token;
    }
    token = token.trim();
    this._token = token === "" ? undefined : token;
  };
  this.get_path = function() {
    if (this.url() && this.token()) {
      return `${this.url()}/api/${this.token()}`;
    }
    return undefined;
  };
  this.get_code = function() {
    const code = codebox.get_code(false);
    const test = codebox.get_code(true);
    return { code, test };
  };
  this.send = async function() {
    const { code, test } = this.get_code();
    try {
      const response = await fetch(this.get_path(), {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          code,
          test
        })
      });
      if (response.status === 404) {
        this.state.show("error 404", false);
        return undefined;
      }
      return await response.json();
    } catch (e) {
      this.state.show(e, false);
      return undefined;
    }
  };
  this.exec = async function() {
    this.state.show("waiting...");
    this.output.clear();
    const response = await this.send();
    if (!response) {
      return false;
    }
    this.points.set(response.points);
    this.output.set(response.text, response.error);
    this.state.hide();
  };

  //| init
  this.token(token);
  this.url(url);

  exec_box[0].sct = this;
}

async function sct_run(btn, id) {
  $(btn).prop("disabled", true);
  const sct = $(`#sct_exec_${id}`)[0].sct;
  await sct.exec();
  $(btn).prop("disabled", false);
}
