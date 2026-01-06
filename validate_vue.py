
import re

def validate_vue_template(filename):
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()

    match = re.search(r'<template>(.*)</template>', content, re.DOTALL)
    if not match:
        print("No template found")
        return

    template_content = match.group(1)
    # Remove HTML comments
    template_content = re.sub(r'<!--.*?-->', '', template_content, flags=re.DOTALL)

    # Tokenize tags
    # Find <tagname ...> or </tagname>
    # We use a regex that matches start or end tags, even with newlines in attributes
    # But checking strictly for '<' followed by optional '/' then name
    
    # Simple state machine
    # 0: Text, 1: Inside Tag Name, 2: Inside Tag Attributes
    
    tags = []
    
    # Regex for finding tags iteratively
    # Matches < /? tagname ( ... ) >
    # This is tricky with regex. 
    # Let's match '<' then optional '/' then identifier
    
    pos = 0
    while True:
        # Find next <
        start_tag_idx = template_content.find('<', pos)
        if start_tag_idx == -1:
            break
            
        # Check if it's a tag
        # e.g. <div or </div
        # Look ahead
        rest = template_content[start_tag_idx+1:]
        
        # Check for end tag /
        is_end_tag = False
        if rest.startswith('/'):
            is_end_tag = True
            rest = rest[1:]
        
        # Get tag name
        m = re.match(r'([a-zA-Z0-9\-:]+)', rest)
        if not m:
            # Not a standard tag, maybe <? or <! or just < symbol
            pos = start_tag_idx + 1
            continue
            
        tag_name = m.group(1)
        
        # Find the closing '>'
        # We need to handle quotes, etc.
        # Simple scan forward handling quote
        
        end_bracket_idx = -1
        current_idx = start_tag_idx + 1 + len(tag_name) + (1 if is_end_tag else 0)
        
        in_quote = None
        
        while current_idx < len(template_content):
            char = template_content[current_idx]
            if in_quote:
                if char == in_quote:
                    in_quote = None
            else:
                if char == "'" or char == '"':
                    in_quote = char
                elif char == '>':
                    end_bracket_idx = current_idx
                    break
            current_idx += 1
            
        if end_bracket_idx == -1:
            print(f"Error: Unclosed tag bracket for <{tag_name} starting at char {start_tag_idx}")
            break
            
        tag_content = template_content[start_tag_idx:end_bracket_idx+1]
        
        # Determine strict line number (approx)
        line_num = template_content[:start_tag_idx].count('\n') + 1
        
        # Check void elements
        void_elements = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']
        
        # Check self closing
        is_self_closing = tag_content.strip().endswith('/>') or tag_name.lower() in void_elements
        
        pos = end_bracket_idx + 1
        
        if is_self_closing:
            continue
            
        if not is_end_tag:
            tags.append({'name': tag_name, 'line': line_num})
        else:
            if not tags:
                print(f"Error: Unexpected end tag </{tag_name}> at line {line_num}")
                continue
            
            last_tag = tags[-1]
            if last_tag['name'] == tag_name:
                tags.pop()
            else:
                print(f"Error: Mismatched tag. Expected </{last_tag['name']}> (opened at line {last_tag['line']}), but found </{tag_name}> at line {line_num}")
                # We can try to continue or stop
                # return 

    if tags:
        print("Error: Unclosed tags remaining:")
        for tag in tags:
            print(f"  <{tag['name']}> opened at line {tag['line']}")
    else:
        print("Template structure appears valid.")

if __name__ == "__main__":
    validate_vue_template('resources/js/Pages/SearchResults.vue')
