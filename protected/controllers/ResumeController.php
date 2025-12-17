<?php
require_once __DIR__ . '/../../vendor/autoload.php'; // adjust path if needed
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class ResumeController extends Controller
{
    // For simplicity, no login required here
    public function accessRules()
    {
        return array(
            array('allow', 'actions'=>array('index', 'upload'), 'users'=>array('*')),
            array('deny', 'users'=>array('*')),
        );
    }

    private function getS3Client()
    {
        return new S3Client([
            'version' => 'latest',
            'region' => getenv('R2_REGION') ?: 'auto',
            'endpoint' => getenv('R2_ENDPOINT'),
            'credentials' => [
                'key' => getenv('R2_ACCESS_KEY'),
                'secret' => getenv('R2_SECRET_KEY'),
            ],
            'suppress_php_deprecation_warning' => true, 
        ]);
    }

    /**
     * Handle resume upload via POST request
     */
    public function actionUpload()
    {
        // Set JSON response header
        header('Content-Type: application/json');
        
        try {
            // Check if file was uploaded
            if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No file uploaded or upload error'
                ]);
                Yii::app()->end();
                return;
            }

            $file = $_FILES['resume'];
            
            // Validate file
            if ($file['size'] == 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'File is empty'
                ]);
                Yii::app()->end();
                return;
            }

            // Check file size (limit to 10MB)
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxFileSize) {
                echo json_encode([
                    'success' => false,
                    'message' => 'File size exceeds 10MB limit'
                ]);
                Yii::app()->end();
                return;
            }

            // Validate file type (PDF and DOC files)
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Only PDF, DOC, DOCX, and TXT files are allowed'
                ]);
                Yii::app()->end();
                return;
            }

            // Get S3 client and bucket
            $s3 = $this->getS3Client();
            $bucket = getenv('R2_BUCKET');

            // Generate unique filename
            $originalName = basename($file['name']);
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $fileName = 'resumes/' . time() . '-' . $safeName;

            // Read file content
            $fileContent = file_get_contents($file['tmp_name']);

            // Upload to R2/S3
            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key' => $fileName,
                'Body' => $fileContent,
                'ContentType' => $mimeType,
                'ACL' => 'private', // Or 'public-read' if you want public access
            ]);

            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Resume uploaded successfully',
                'filePath' => $fileName,
                'fileName' => $originalName,
                'fileSize' => $file['size'],
                'fileType' => $mimeType,
                'uploadedAt' => date('Y-m-d H:i:s')
            ]);
            Yii::app()->end();

        } catch (AwsException $e) {
            // Log error
            Yii::log('S3 Upload Error: ' . $e->getMessage(), 'error', 'application.resume');
            
            echo json_encode([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ]);
            Yii::app()->end();
            
        } catch (Exception $e) {
            // Log error
            Yii::log('Upload Error: ' . $e->getMessage(), 'error', 'application.resume');
            
            echo json_encode([
                'success' => false,
                'message' => 'Upload failed'
            ]);
            Yii::app()->end();
        }
    }

    /**
     * List all resumes (existing action)
     */
    public function actionIndex()
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = getenv('R2_BUCKET');

            $result = $s3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => 'resumes/',
            ]);

            $files = [];
            if (!empty($result['Contents'])) {
                foreach ($result['Contents'] as $obj) {
                    // Skip if it's a folder (ends with /)
                    if (substr($obj['Key'], -1) === '/') {
                        continue;
                    }

                    // Generate presigned URL valid for 20 minutes
                    $cmd = $s3->getCommand('GetObject', [
                        'Bucket' => $bucket,
                        'Key' => $obj['Key']
                    ]);
                    $presignedUrl = (string) $s3->createPresignedRequest($cmd, '+20 minutes')->getUri();

                    $files[] = [
                        'key' => $obj['Key'],
                        'size' => $obj['Size'],
                        'lastModified' => $obj['LastModified']->format('Y-m-d H:i:s'),
                        'url' => $presignedUrl,
                        'fileName' => basename($obj['Key']),
                    ];
                }
            }

            // Render view
            $this->render('index', ['resumes' => $files]);

        } catch (AwsException $e) {
            Yii::app()->user->setFlash('error', 'Error fetching resumes: ' . $e->getMessage());
            $this->render('index', ['resumes' => []]);
        }
    }

    /**
     * Delete a resume
     */
    public function actionDelete($key)
    {
        header('Content-Type: application/json');
        
        try {
            $s3 = $this->getS3Client();
            $bucket = getenv('R2_BUCKET');

            // Decode the key (might be URL encoded)
            $key = urldecode($key);

            $result = $s3->deleteObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Resume deleted successfully'
            ]);
            Yii::app()->end();

        } catch (AwsException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ]);
            Yii::app()->end();
        }
    }

    /**
     * Download a resume (generate presigned URL for download)
     */
    public function actionDownload($key)
    {
        try {
            $s3 = $this->getS3Client();
            $bucket = getenv('R2_BUCKET');

            // Decode the key
            $key = urldecode($key);

            // Generate presigned URL valid for 5 minutes for download
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $key,
                'ResponseContentDisposition' => 'attachment; filename="' . basename($key) . '"'
            ]);
            
            $presignedUrl = (string) $s3->createPresignedRequest($cmd, '+5 minutes')->getUri();

            // Redirect to the presigned URL
            $this->redirect($presignedUrl);

        } catch (AwsException $e) {
            Yii::app()->user->setFlash('error', 'Error generating download link: ' . $e->getMessage());
            $this->redirect(array('index'));
        }
    }
}