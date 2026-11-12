<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_deepler\local\services;

use DeepL\DeepLClient;
use DeepL\DocumentHandle;
use DeepL\DocumentStatus;
use stored_file;

/**
 * Service to interact with DeepL Document Translation API.
 *
 * @package    local_deepler
 * @copyright  2026 Bruno Baudry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document_translator {
    /** @var DeepLClient DeepL client instance. */
    protected DeepLClient $client;

    /**
     * Constructor.
     *
     * @param DeepLClient $client
     */
    public function __construct(DeepLClient $client) {
        $this->client = $client;
    }

    /**
     * Upload a stored_file to DeepL for translation.
     *
     * @param stored_file $file
     * @param string|null $source Source language code (or null for auto-detect).
     * @param string $target Target language code.
     * @param array $options Translation options (formality, glossary, etc.).
     * @return DocumentHandle
     * @throws \DeepL\DeepLException
     */
    public function upload(stored_file $file, ?string $source, string $target, array $options = []): DocumentHandle {
        $tempdir = make_request_directory();
        $temppath = $tempdir . '/' . $file->get_filename();
        $file->copy_content_to($temppath);

        try {
            $handle = $this->client->translateDocumentUpload($temppath, $source, $target, $options);
        } finally {
            if (file_exists($temppath)) {
                @unlink($temppath);
            }
        }

        return $handle;
    }

    /**
     * Query the status of a document translation from DeepL.
     *
     * @param DocumentHandle $handle
     * @return DocumentStatus
     * @throws \DeepL\DeepLException
     */
    public function status(DocumentHandle $handle): DocumentStatus {
        return $this->client->translateDocumentGetStatus($handle);
    }

    /**
     * Download the completed translated document from DeepL to specified output path.
     *
     * @param DocumentHandle $handle
     * @param string $outpath
     * @return void
     * @throws \DeepL\DeepLException
     */
    public function download(DocumentHandle $handle, string $outpath): void {
        $this->client->translateDocumentDownload($handle, $outpath);
    }
}
