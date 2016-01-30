<?php

namespace DDD\Service\Finance\Expense;

use DDD\Dao\Finance\Expense\ExpenseItemAttachments;
use DDD\Service\ServiceBase;
use DDD\Dao\Finance\Expense\ExpenseAttachments as ExpenseAttachmentsDao;
use Library\Utility\Helper;

class ExpenseAttachments extends ServiceBase
{
    /**
     * @todo: in thin method we can get also a files, that doesn't written in DB if any exists
     *
     * @param int $expenseId
     * @return array
     */
    public function getAttachmentListForPreview($expenseId)
    {
        $attachmentsDao = $this->getAttachementDao();
        $attachmentsDomain = $attachmentsDao->getAttachmentsForPreview($expenseId);
        $attachmentList = [];

        if ($attachmentsDomain->count()) {
            foreach ($attachmentsDomain as $attachment) {
                $filePath = $this->getExpenseImagePath($expenseId, $attachment['filename'], $attachment['date_created']);

                if (is_readable($filePath)) {
                    $filesize = Helper::humanFilesize(filesize($filePath));
                    $extension = pathinfo($attachment['filename'], PATHINFO_EXTENSION);

                    array_push($attachmentList, [
                        'id' => $attachment['id'],
                        'name' => $attachment['filename'],
                        'size' => $filesize,
                        'path' => $filePath,
                        'extension' => $extension,
                        'isImage' => in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'gif']) ? 1 : 0
                    ]);
                }
            }
        }

        return $attachmentList;
    }

    /**
     * @todo: in thin method we can get also a files, that doesn't written in DB if any exists
     *
     * @param array $itemIdList
     * @return array
     */
    public function getItemAttachmentListForPreview($itemIdList)
    {
        /**
         * @var ExpenseItemAttachments $attachmentsDao
         * @var \DDD\Domain\Finance\Expense\ExpenseItemAttachments $attachment
         */
        $attachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
        $attachmentList = [];

        if (count($itemIdList)) {
            $attachmentsDomain = $attachmentsDao->getAttachmentsForPreview($itemIdList);

            if ($attachmentsDomain->count()) {
                foreach ($attachmentsDomain as $attachment) {
                    $filePath = $this->getExpenseItemImagePath($attachment->getExpenseId(), $attachment->getItemId(), $attachment->getFilename(), $attachment->getDateCreatedNeededFormat());

                    if (is_readable($filePath)) {
                        $filesize = Helper::humanFilesize(filesize($filePath));
                        $extension = pathinfo($attachment->getFilename(), PATHINFO_EXTENSION);

                        $attachmentList[$attachment->getItemId()] = [
                            'id' => $attachment->getId(),
                            'name' => $attachment->getFilename(),
                            'size' => $filesize,
                            'path' => $filePath,
                            'extension' => $extension,
                            'isImage' => in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'gif']) ? 1 : 0
                        ];
                    }
                }
            }
        }

        return $attachmentList;
    }

    /**
     * @param $expenseId
     * @param $filename
     * @param $date
     * @return string
     */
    public function getExpenseImagePath($expenseId, $filename, $date)
    {
        $date = date('Y/m/d', strtotime($date));

        return "/ginosi/uploads/expense/{$date}/{$expenseId}/{$filename}";
    }

    /**
     * @param int $expenseId
     * @param int $itemId
     * @param string $filename
     * @param string $date
     * @return string
     */
    public function getExpenseItemImagePath($expenseId, $itemId, $filename, $date)
    {

        return "/ginosi/uploads/expense/items/{$date}/{$expenseId}/{$itemId}/{$filename}";
    }

    /**
     * @return ExpenseAttachmentsDao
     */
    private function getAttachementDao()
    {
        return new ExpenseAttachmentsDao($this->getServiceLocator());
    }
}
