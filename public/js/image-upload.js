class MyUploadAdapter {
    constructor(loader) {
        // 构造方法
        this.loader = loader;
    }

    // 开始上传
    upload() {
        return this.loader.file
            .then(file => new Promise((resolve, reject) => {
                this._initRequest();
                this._initListeners(resolve, reject, file);
                this._sendRequest(file);
            }));
    }

    // 停止上传
    abort() {
        if (this.xhr) {
            this.xhr.abort();
        }
    }

    // 初始化
    _initRequest() {
        const xhr = this.xhr = new XMLHttpRequest();
        xhr.open('POST', imageUploadUrl, true);
        xhr.responseType = 'json';
    }

    // 初始化监听
    _initListeners(resolve, reject, file) {
        const xhr = this.xhr;
        const loader = this.loader;
        const genericErrorText = `图片上传失败：${file.name}.`;

        xhr.addEventListener('error', () => reject(genericErrorText));
        xhr.addEventListener('abort', () => reject());
        xhr.addEventListener('load', () => {
            const response = xhr.response;
            if (!response || response.error) {
                return reject(response && response.error ? response.error.message : genericErrorText);
            }
            resolve({
                default: response.url
            });
        });
        if (xhr.upload) {
            xhr.upload.addEventListener('progress', evt => {
                if (evt.lengthComputable) {
                    loader.uploadTotal = evt.total;
                    loader.uploaded = evt.loaded;
                }
            });
        }
    }

    // 请求
    _sendRequest(file) {
        const data = new FormData();
        data.append('upload', file);
        this.xhr.send(data);
    }
}

function MyCustomUploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        return new MyUploadAdapter(loader);
    };
}