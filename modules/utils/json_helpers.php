<?php
/**
 * Utility functions for safely reading and writing JSON files.
 * Includes file locking for write operations to prevent data corruption.
 */

/**
 * Reads data from a JSON file.
 *
 * @param string $filePath The full path to the JSON file.
 * @return array|null Returns the decoded data as an associative array,
 * or an empty array if the file is empty or doesn't exist,
 * or null if decoding fails for a non-empty file.
 */
function readJsonFile(string $filePath): ?array {
    if (!file_exists($filePath) || filesize($filePath) === 0) {
        // Return empty array if file doesn't exist or is empty
        // This allows initializing data structures easily
        return [];
    }

    $jsonContent = file_get_contents($filePath);
    if ($jsonContent === false) {
        // Handle file read error
        error_log("Error reading JSON file: " . $filePath);
        return null; // Indicate error
    }

    $data = json_decode($jsonContent, true); // Decode as associative array

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in file " . $filePath . ": " . json_last_error_msg());
        return null; // Indicate error
    }

    // Ensure we return an array even if the JSON root is not an array/object
    // Although for our purpose (products, sales) it should always be an array.
    return is_array($data) ? $data : [];
}

/**
 * Writes data to a JSON file safely using file locking.
 * This will overwrite the existing file content.
 *
 * @param string $filePath The full path to the JSON file.
 * @param mixed $data The PHP data (usually an array) to encode and write.
 * @return bool Returns true on success, false on failure.
 */
function writeJsonFile(string $filePath, $data): bool {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($jsonData === false) {
        error_log("JSON encode error: " . json_last_error_msg());
        return false; // Indicate encoding error
    }

    // Open file handle with 'c' mode: Create if not exists, place pointer at beginning.
    // Does not truncate yet, allowing us to lock first.
    $fileHandle = fopen($filePath, 'c');
    if ($fileHandle === false) {
        error_log("Error opening file for writing: " . $filePath);
        return false;
    }

    // Attempt to get an exclusive lock (blocks other processes trying to lock)
    if (flock($fileHandle, LOCK_EX)) {
        // Truncate the file to zero length AFTER getting the lock
        if (!ftruncate($fileHandle, 0)) {
            error_log("Error truncating file: " . $filePath);
            flock($fileHandle, LOCK_UN); // Release lock before returning
            fclose($fileHandle);
            return false;
        }

        // Write the JSON data
        $bytesWritten = fwrite($fileHandle, $jsonData);
        if ($bytesWritten === false) {
            error_log("Error writing to file: " . $filePath);
            flock($fileHandle, LOCK_UN); // Release lock
            fclose($fileHandle);
            return false;
        }

        // Ensure all buffered output is written
        fflush($fileHandle);

        // Release the lock
        flock($fileHandle, LOCK_UN);
    } else {
        // Could not get the lock
        error_log("Error acquiring exclusive lock for file: " . $filePath);
        fclose($fileHandle); // Close the handle even if lock failed
        return false;
    }

    // Close the file handle
    fclose($fileHandle);

    return true;
}

?>