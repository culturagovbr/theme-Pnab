app.component('entity-file', {
    template: $TEMPLATES['entity-file'],
    emits: ['delete', 'setFile', 'uploaded'],

    setup(props, { slots }) {
        const text = Utils.getTexts('entity-file');
        const hasSlot = name => !!slots[name];
        return { text, hasSlot }
    },

    props: {
        entity: {
            type: Entity,
            required: true
        },
        groupName: {
            type: String,
            required: true
        },
        titleModal: {
            type: String,
            default: ""
        },
        title: {
            type: String,
            default: ""
        },
        description: {
            type: String
        },
        uploadFormTitle: {
            type: String,
            required: false
        },
        required: {
            type: Boolean,
            require: false
        },
        editable: {
            type: Boolean,
            require: false
        },
        disableName: {
            type: Boolean,
            default: false
        },
        enableDescription: {
            type: Boolean,
            default: false
        },
        classes: {
            type: [String, Array, Object],
            required: false
        },
        downloadOnly: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        defaultFile: {
            type: Object,
            required: false
        },
        beforeUpload: {
            type: Function,
            required: false
        },
        uploadOnSubmit: {
            type: Boolean,
            default: true,
        },
        buttonTextValue: {
            type: String,
            required: false,
            default: 'Enviar'
        },
        allowedFileTypes: {
            type: Array,
            required: false,
            default: () => []
        },
    },

    data() {
        return {
            formData: {},
            newFile: {},
            file: this.entity.files?.[this.groupName] || null,
            maxFileSize: $MAPAS.maxUploadSizeFormatted,
            loading: false,
            localErrors: []
        }
    },

    computed: {
        acceptAttribute() {
            if (this.allowedFileTypes && this.allowedFileTypes.length > 0) {
                return this.allowedFileTypes.join(',');
            }
            return null;
        },
        allowedFileTypesLabel() {
            if (!this.allowedFileTypes || this.allowedFileTypes.length === 0) {
                return '';
            }

            const mimeToExtension = {
                'application/pdf': 'PDF',
                'image/jpeg': 'JPEG',
                'image/png': 'PNG',
                'image/gif': 'GIF',
                'application/msword': 'DOC',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
                'application/vnd.oasis.opendocument.text': 'ODT',
                'application/vnd.ms-excel': 'XLS',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
                'application/vnd.oasis.opendocument.spreadsheet': 'ODS',
                'text/csv': 'CSV',
                'application/vnd.ms-powerpoint': 'PPT',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'PPTX',
                'application/vnd.oasis.opendocument.presentation': 'ODP',
                'application/zip': 'ZIP',
                'application/x-rar-compressed': 'RAR',
                'video/mp4': 'MP4',
                'video/x-msvideo': 'AVI',
                'video/quicktime': 'MOV',
                'audio/mpeg': 'MP3',
                'audio/wav': 'WAV',
                'text/plain': 'TXT'
            };

            const extensions = this.allowedFileTypes.map(mime =>
                mimeToExtension[mime] || mime
            );

            return extensions.join(', ');
        },
        hasErrors() {
            return (this.entity.__validationErrors?.[this.groupName]?.length > 0) || this.localErrors.length > 0;
        },
        getErrors() {
            let errors = this.entity.__validationErrors?.[this.groupName] || [];
            if (this.localErrors.length > 0) {
                errors = errors.concat(this.localErrors);
            }
            return [...new Set(errors)];
        }
    },

    updated() {
        if (this.uploadOnSubmit) {
            this.file = this.entity.files?.[this.groupName] || null;
        }
    },

    methods: {
        setFile(event) {
            this.newFile = event.target.files[0];

            if (!this.uploadOnSubmit && this.newFile) {
                this.file = this.newFile;
            }

            if (this.entity.__validationErrors) {
                this.entity.__validationErrors[this.groupName] = [];
            } else {
                this.entity.__validationErrors = { [this.groupName]: [] };
            }
            this.localErrors = [];

            let maxBytes = 0;
            if (this.maxFileSize && typeof this.maxFileSize === 'string') {
                const match = this.maxFileSize.match(/([\d.,]+)\s*(MB|KB|GB|B)/i);
                if (match) {
                    const value = parseFloat(match[1].replace(',', '.'));
                    const unit = match[2].toUpperCase();
                    if (unit === 'GB') maxBytes = value * 1024 * 1024 * 1024;
                    else if (unit === 'MB') maxBytes = value * 1024 * 1024;
                    else if (unit === 'KB') maxBytes = value * 1024;
                    else if (unit === 'B') maxBytes = value;
                }
            }
            
            if (maxBytes > 0 && this.newFile && this.newFile.size > maxBytes) {
                const msg = `O arquivo excede o limite permitido (${this.maxFileSize}).`;
                this.entity.__validationErrors[this.groupName] = [msg];
                this.localErrors = [msg];
                this.newFile = {};
                event.target.value = '';
                return;
            }

            this.$emit('setFile', this.newFile);
        },

        async upload(modal) {
            if (this.entity.__validationErrors) {
                this.entity.__validationErrors[this.groupName] = [];
            } else {
                this.entity.__validationErrors = { [this.groupName]: [] };
            }
            this.localErrors = [];
            this.loading = true;

            let data = {
                description: this.formData.description,
                group: this.groupName,
            };

            if (this.beforeUpload) {
                await this.beforeUpload({
                    data,
                    file: this.newFile
                });
            }

            this.entity.disableMessages();
            try {
                const response = await this.entity.upload(this.newFile, data);
                this.file = response;
                this.$emit('uploaded', this);
                this.loading = false;
                this.entity.enableMessages();

                this.file = null;
                this.newFile = {};

                if (modal) {
                    modal.close();
                }

            } catch (e) {
                this.loading = false;
                let errorMessageStr = "Erro ao enviar o arquivo";
                if (e && (e.status === 413 || (e.response && e.response.status === 413))) {
                    errorMessageStr = `O arquivo excede o limite permitido (${this.maxFileSize}).`;
                } else if (e && e.error && typeof e.error === 'string') {
                    errorMessageStr = e.error;
                } else if (e && e.errorMessage && typeof e.errorMessage === 'string') {
                    errorMessageStr = e.errorMessage;
                } else if (e && e.error && e.data && e.data[this.groupName]) {
                    errorMessageStr = Array.isArray(e.data[this.groupName]) ? e.data[this.groupName][0] : e.data[this.groupName];
                } else if (e && e.message) {
                    errorMessageStr = e.message;
                } else if (typeof e === 'string') {
                    errorMessageStr = e;
                } else {
                    console.error(e);
                }

                if (this.entity.__validationErrors) {
                    this.entity.__validationErrors[this.groupName] = [errorMessageStr];
                } else {
                    this.entity.__validationErrors = { [this.groupName]: [errorMessageStr] };
                }
                this.localErrors = [errorMessageStr];
            }

            return true;
        },

        async submit(modal) {
            if (this.uploadOnSubmit) {
                await this.upload(modal);
            } else {
                modal.close();
            }
        },

        async deleteFile(file) {
            await file.delete();
            this.file = null;
            this.newFile = {};

            this.$emit('delete', file);
        }
    },
});
