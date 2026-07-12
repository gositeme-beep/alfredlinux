import sys

def decode_bencode(data, index=0):
    if index >= len(data):
        raise ValueError("Unexpected end of data")
    char = data[index:index+1]
    
    if char == b'i':
        end = data.find(b'e', index + 1)
        return int(data[index+1:end]), end + 1
    elif char == b'l':
        lst = []
        index += 1
        while data[index:index+1] != b'e':
            val, index = decode_bencode(data, index)
            lst.append(val)
        return lst, index + 1
    elif char == b'd':
        dct = {}
        index += 1
        while data[index:index+1] != b'e':
            key, index = decode_bencode(data, index)
            val, index = decode_bencode(data, index)
            dct[key] = val
        return dct, index + 1
    elif char in b'0123456789':
        colon = data.find(b':', index)
        length = int(data[index:colon])
        start = colon + 1
        return data[start:start+length], start + length
    else:
        raise ValueError(f"Invalid bencode format at index {index}")

def encode_bencode(obj):
    if isinstance(obj, int):
        return b'i' + str(obj).encode() + b'e'
    elif isinstance(obj, bytes):
        return str(len(obj)).encode() + b':' + obj
    elif isinstance(obj, str):
        encoded = obj.encode('utf-8')
        return str(len(encoded)).encode() + b':' + encoded
    elif isinstance(obj, list):
        return b'l' + b''.join(encode_bencode(item) for item in obj) + b'e'
    elif isinstance(obj, dict):
        # Keys must be sorted byte strings
        sorted_keys = sorted(obj.keys())
        return b'd' + b''.join(encode_bencode(k) + encode_bencode(obj[k]) for k in sorted_keys) + b'e'
    else:
        raise TypeError(f"Unsupported type: {type(obj)}")

if __name__ == "__main__":
    torrent_path = sys.argv[1]
    webseed_url = sys.argv[2].encode('utf-8')
    
    with open(torrent_path, 'rb') as f:
        data = f.read()
    
    parsed, _ = decode_bencode(data)
    
    # Add web seed
    parsed[b'url-list'] = [webseed_url]
    
    with open(torrent_path, 'wb') as f:
        f.write(encode_bencode(parsed))
    print("Injected webseed successfully.")
