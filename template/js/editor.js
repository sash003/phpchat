function addEditor(tag, tagEnd) {
  tagEnd = tagEnd || '';
  var obj = document.getElementById("message_text");
  if (document.selection) obj.value += tag + tagEnd;
  else if (typeof obj.selectionStart == "number") {
    var start = obj.selectionStart;
    var end = obj.selectionEnd;
    var value = obj.value;
    obj.select();
    if (start != end) {
      obj.value =
        value.substr(0, start) +
        tag +
        value.substr(start, end - start) +
        tagEnd +
        value.substr(end);
      obj.setSelectionRange(start, end + tag.length + tagEnd.length + 5);
    } else {
      obj.value = value.substr(0, start) + tag + tagEnd + value.substr(start);
      obj.setSelectionRange(start + tag.length, start + tag.length);
    }
  }
}