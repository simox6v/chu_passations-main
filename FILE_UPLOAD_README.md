# File Upload Functionality for CHU Passations

## Overview
This document describes the file upload functionality that has been added to the CHU Passations system, allowing users to attach files to patient handover records (passations).

## Features Added

### 1. File Upload Field
- **Location**: Added to the "Consignes" (instructions) section in both create and edit forms
- **Label**: "Pièce jointe" (File Attachment)
- **File Types**: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, TXT
- **File Size Limit**: 10MB maximum
- **Required**: No (optional field)

### 2. File Storage
- **Storage Location**: `storage/app/public/passations/`
- **Public Access**: Files are accessible via `/storage/passations/` URL
- **File Naming**: `timestamp_originalfilename.ext` (e.g., `1703123456_document.pdf`)

### 3. File Management
- **Upload**: Users can upload files when creating or editing passations
- **Download**: Files can be downloaded via the download button in show modals
- **Delete**: Users can delete attached files (with proper permissions)
- **Replace**: Files can be replaced by uploading new ones

### 4. User Interface Updates

#### Create Modal
- File upload input field below the rich text editor
- File type restrictions and size limit information
- Form validation for file uploads

#### Edit Modal
- Shows current file if one exists
- Download link for current file
- Delete button for current file (if not time-restricted)
- File upload field for replacement

#### Show Modal
- Displays file attachment section if file exists
- Download button with file icon
- File name display

#### Table Views
- File indicator badge in passations table
- Green "Fichier" badge shows when attachments exist

### 5. Security Features
- **File Type Validation**: Only allowed file types can be uploaded
- **Size Limits**: 10MB maximum file size
- **Permission Checks**: Users can only manage their own files (unless admin)
- **Time Restrictions**: Non-admin users cannot modify files after 30 minutes
- **CSRF Protection**: All file operations are CSRF protected

### 6. Audit Trail
- File uploads are logged in the `passation_edit_logs` table
- File deletions are logged with old and new values
- File replacements are logged showing the change

## Technical Implementation

### Database Changes
- Added `file_attachment` column to `passations` table
- Column is nullable and stores the filename

### New Routes
- `GET /passations/{passation}/download` - Download file attachment
- `DELETE /passations/{passation}/file` - Delete file attachment

### Controller Methods
- `downloadFile()` - Handles file downloads
- `deleteFile()` - Handles file deletions
- Updated `store()` and `update()` methods for file handling

### File Storage
- Uses Laravel's public disk storage
- Files stored in `storage/app/public/passations/` directory
- Public access via symbolic link to `public/storage/`

## Usage Instructions

### For Users
1. **Uploading Files**: 
   - Click "Ajouter une passation" or edit existing passation
   - Use the "Pièce jointe" field to select a file
   - Supported formats: PDF, DOC, DOCX, JPG, PNG, GIF, TXT
   - Maximum size: 10MB

2. **Downloading Files**:
   - Click the "Voir" (View) button on any passation
   - If a file is attached, click the "Télécharger" (Download) button

3. **Managing Files**:
   - Edit a passation to replace or delete attached files
   - Use the "Supprimer" (Delete) button to remove files
   - Upload new files to replace existing ones

### For Administrators
- Full access to all file operations
- No time restrictions on file modifications
- Can manage files for all passations

## File Management Best Practices

1. **File Naming**: Use descriptive names for uploaded files
2. **File Types**: Prefer PDF for documents, JPG/PNG for images
3. **File Size**: Keep files under 5MB when possible for better performance
4. **Backup**: Important files should be backed up separately
5. **Cleanup**: Remove unnecessary files to save storage space

## Error Handling

- **File Too Large**: Shows validation error if file exceeds 10MB
- **Invalid File Type**: Shows validation error for unsupported formats
- **Upload Failures**: Displays appropriate error messages
- **Permission Denied**: Shows access denied for unauthorized operations

## Future Enhancements

Potential improvements for future versions:
- Image preview for image files
- File versioning system
- Bulk file operations
- File compression for large files
- Integration with document management systems
- File sharing between users
- Advanced file search and filtering

## Support

For technical support or questions about the file upload functionality, please contact the development team or refer to the main application documentation.
