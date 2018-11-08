# TextToSpeech Job Service

> API to generate TextToSpeech audio using AWS Polly.

# Routes

## `/`
> **Request Type:** `GET`

#### Request
> N/A


### **Response** 
> **Content-Type:** `text/html`

```
APP_NAME
```

-----

## `/api/v1` Routes

### `/items`
> **Request Type:** `GET`   
> **Route Name:** `items.list`   
> **Description**   
> Get a list of all the TTSItems in the system.


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  "success":  true,
  "items":  [
  {
    "item_id": 1234,
    "unique_id": "unique0example0id",
    "name": "Voice-Test",
    "status": "Processed"
  }
  ],
  "messages": [
  "any error messages go here.",
  "if there are error messages, `success` will probably be `false`."
  ]
}
```

### `/items/create`
> **Request Type:** `POST`   
> **Route Name:** `items.create`   
> **Description**   
> Create new TTSItem(s) and add to the job queue. 


#### Request
> **Content-Type:** `application/json`   

**Parameters:**

- `text`: **Required**. The text to convert to audio.
- `voices`: **Required**. can be either a single integer value or an array of integer values.
- `name`: Optional. Name of the item.
- `output_format`: Optional. Audio output format. See [TTS output formats](#tts-output-formats) below for options.

```json
{
  "name": "Assessment-1",
  "text": "The text to convert to TextToSpeech audio",
  "voices": [10, 12],
  "output_format": "mp3"
}
```


#### Response
> **Content-Type:** `application/json`

**Parameters:**
```json
{
  "success":  true,
  "items":  [
    {
      "id":             23,
      "name":           "Assessment-1",
      "user_id":        null,
      "status":         "Processed",
      "voice_id":       "Matthew",
      "output_format":  "mp3",
      "unique_id":      "1139921d00c38b6c4f30cdb0c6c66a2",
      "text_file":      "text\/1139921d00c38b6c4f30cdb0c6c66a2.txt",
      "audio_file":     "audio\/1139921d00c38b6c4f30cdb0c6c66a2.mp3",
      "updated_at":     "2018-11-07 14:04:44",
      "created_at":     "2018-11-07 14:04:44",
    },
    {
      "id":             24,
      "name":           "Assessment-1",
      "user_id":        null,
      "status":         "Created",
      "voice_id":       "Joanna",
      "output_format":  "mp3",
      "unique_id":      "fcdb0c6c66ab707597f30cdb0c696a63",
      "text_file":      "text\/fcdb0c6c66ab707597f30cdb0c696a63.txt",
      "audio_file":     "audio\/fcdb0c6c66ab707597f30cdb0c696a63.mp3",
      "updated_at":     "2018-11-07 14:04:50",
      "created_at":     "2018-11-07 14:04:50"
    }
  ],
  "messages": [
    "any error messages"
  ]
}
```


### `/items/{item_id}/regenerate`
> **Request Type:** `GET`   
> **Route Name:** `items.regenerate`   
> **Description**   
> Regenerate the audio for an existing TTS item


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  "success":  true,
  "messages": [
    ""
  ]
}
```


### `/items/{item_id}/status`
> **Request Type:** `GET`   
> **Route Name:** `items.status`   
> **Description**   
> Get the status of a TTS item.


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  "item_id":    24,
  "unique_id":  "fcdb0c6c66ab707597f30cdb0c696a63",
  "name":       "Sample Audio File",
  "status":     "Processed",
  "audio_url":  "audio\/fcdb0c6c66ab707597f30cdb0c696a63.mp3",
  "messages":   [
    "foo",
    "bar"
  ]
}
```


### `/items/{item_id}/text`
> **Request Type:** `GET`   
> **Route Name:** `items.text`   
> **Description**   
> Get the cached text content of a TTS item.


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  "item_id":    24,
  "unique_id":  "fcdb0c6c66ab707597f30cdb0c696a63",
  "text":       "Test audio item content here.",
  "messages":   [
    "foo",
    "bar"
  ]
}
```


### `/items/{item_id}/audio`
> **Request Type:** `GET`   
> **Route Name:** `items.audio`   
> **Description**   
> Alias for `/items/{item_id}/audio/download`.


### `/items/{item_id}/audio/download`
> **Request Type:** `GET`   
> **Route Name:** `items.audio.download`   
> **Description**   
> Download the audio file for the specified TTS item if available.


#### Request
> N/A


#### Response
> **Content-Type:** `application/json | audio/mpeg | audio/ogg | audio/wav`

- json response on error:
```json
{
  "success":    false,
  "messsages":  [
    "error",
    "messages"
  ]
}
```
- otherwise, the audio file is returned as a file download.


### `/items/{item_id}/audio/stream`
> **Request Type:** `GET`   
> **Route Name:** `items.audio.stream`   
> **Description**   
> Stream the audio file of a TTS item if available


#### **Parameters**
> N/A


#### Response
> **Content-Type:** `application/json | audio/mpeg | audio/ogg | audio/wav`

- json response on error:
```json
{
  "success":  false,
  "messages": []
}
```
- otherwise, the audio file is returned as a stream.

### `/items/{item_id}/delete`
> **Request Type:** `DELETE`   
> **Route Name:** `items.delete`   
> **Description**   
> Delete a TTS item and it's associated files in S3.


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  "success":  true,
  "messages": []
}
```


### `/tts/voices`
> **Request Type:** `GET`   
> **Route Name:** `tts.voices`   
> **Description**   
> Get voices available in the system.


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  "1": {
    "preferred": false,
    "gender": "m",
    "name": "Russell",
    "language": "en-AU"
  },
  "2": {
    "preferred": false,
    "gender": "f",
    "name": "Nicole",
    "language": "en-AU"
  },
  "3": {
    "preferred": true,
    "gender": "m",
    "name": "Brian",
    "language": "en-GB"
  },
  "4": {
    "preferred": true,
    "gender": "f",
    "name": "Amy",
    "language": "en-GB"
  },
  "5": {
    "preferred": false,
    "gender": "f",
    "name": "Emma",
    "language": "en-GB"
  },
  "6": {
    "preferred": false,
    "gender": "f",
    "name": "Aditi",
    "language": "en-IN"
  },
  "7": {
    "preferred": false,
    "gender": "f",
    "name": "Raveena",
    "language": "en-IN"
  },
  "8": {
    "preferred": false,
    "gender": "m",
    "name": "Joey",
    "language": "en-US"
  },
  "9": {
    "preferred": false,
    "gender": "m",
    "name": "Justin",
    "language": "en-US"
  },
  "10": {
    "preferred": true,
    "gender": "m",
    "name": "Matthew",
    "language": "en-US"
  },
  "11": {
    "preferred": false,
    "gender": "f",
    "name": "Ivy",
    "language": "en-US"
  },
  "12": {
    "preferred": true,
    "gender": "f",
    "name": "Joanna",
    "language": "en-US"
  },
  "13": {
    "preferred": false,
    "gender": "f",
    "name": "Kendra",
    "language": "en-US"
  },
  "14": {
    "preferred": false,
    "gender": "f",
    "name": "Kimberly",
    "language": "en-US"
  },
  "15": {
    "preferred": false,
    "gender": "f",
    "name": "Salli",
    "language": "en-US"
  },
  "16": {
    "preferred": false,
    "gender": "m",
    "name": "Geraint",
    "language": "en-GB-WLS"
  }
}
```


### `/tts/ssml-replacements`
> **Request Type:** `GET`   
> **Route Name:** `tts.ssml.replacements`   
> **Description**   
> Get the SSML replacements that will be applied to TTS items. 


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  " & ": " and ",
  "(": "<s>(",
  ")": ")</s>",
  ")</s>.": ")</s>",
  ")</s>;": ")</s>",
  ")</s>:": ")</s>"
}
```


### `/tts/output-formats` {#tts-output-formats}
> **Request Type:** `GET`   
> **Route Name:** `tts.output.formats`   
> **Description**   
> Get the audio output formats available.


#### Request
> N/A


#### Response
> **Content-Type:** `application/json`

```json
{
  "mp3": "mp3",
  "ogg_vorbis": "ogg",
  "pcm": "pcm"
}
```