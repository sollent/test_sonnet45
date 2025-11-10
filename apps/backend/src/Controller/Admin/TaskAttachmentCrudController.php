<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\TaskAttachment;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class TaskAttachmentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return TaskAttachment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Attachment')
            ->setEntityLabelInPlural('Task Attachments')
            ->setPageTitle('index', 'Attachment Management')
            ->setPageTitle('detail', fn (TaskAttachment $attachment) => sprintf(
                'Attachment: %s',
                $attachment->getOriginalName()
            ))

            // Pagination
            ->setPaginatorPageSize(30)
            ->setPaginatorRangeSize(4)

            // Search
            ->setSearchFields(['originalName', 'fileName', 'mimeType', 'task.title', 'uploadedBy.email'])

            // Default sort: newest first
            ->setDefaultSort(['uploadedAt' => 'DESC'])

            // Date format
            ->setDateTimeFormat('dd.MM.yyyy HH:mm')

            // Other settings
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        // ID field - only on index
        yield IdField::new('id', 'ID')
            ->onlyOnIndex();

        // Parent Task - required association (read-only in admin)
        yield AssociationField::new('task', 'Task')
            ->setCrudController(TaskCrudController::class)
            ->formatValue(function ($value, TaskAttachment $attachment) {
                $task = $attachment->getTask();
                if (!$task) {
                    return '<span class="badge badge-secondary">No task</span>';
                }

                $statusClass = $task->isCompleted() ? 'success' : 'secondary';
                $statusIcon = $task->isCompleted() ? 'check-circle' : 'circle-o';

                return sprintf(
                    '<i class="fa fa-%s text-%s"></i> <strong>%s</strong> <small class="text-muted">(#%d)</small>',
                    $statusIcon,
                    $statusClass,
                    htmlspecialchars($task->getTitle()),
                    $task->getId()
                );
            })
            ->setColumns('col-md-6')
            ->onlyOnDetail();

        // Task (index/edit) - simplified
        yield AssociationField::new('task', 'Task')
            ->formatValue(function ($value, TaskAttachment $attachment) {
                $task = $attachment->getTask();
                return $task ? sprintf('#%d: %s', $task->getId(), $task->getTitle()) : '-';
            })
            ->hideOnDetail()
            ->setColumns('col-md-4');

        // Uploaded By
        yield AssociationField::new('uploadedBy', 'Uploaded By')
            ->setCrudController(UserCrudController::class)
            ->formatValue(function ($value, TaskAttachment $attachment) {
                return $attachment->getUploadedBy() ? $attachment->getUploadedBy()->getEmail() : '-';
            })
            ->setColumns('col-md-4');

        // File Type - with icon
        yield TextField::new('fileType', 'Type')
            ->formatValue(function ($value, TaskAttachment $attachment) {
                $type = $attachment->getFileType();
                $iconMap = [
                    'image' => ['icon' => 'file-image-o', 'class' => 'success'],
                    'document' => ['icon' => 'file-text-o', 'class' => 'primary'],
                    'video' => ['icon' => 'file-video-o', 'class' => 'warning'],
                    'other' => ['icon' => 'file-o', 'class' => 'secondary'],
                ];

                $config = $iconMap[$type] ?? $iconMap['other'];

                return sprintf(
                    '<span class="badge badge-%s"><i class="fa fa-%s"></i> %s</span>',
                    $config['class'],
                    $config['icon'],
                    strtoupper($type)
                );
            })
            ->setColumns('col-md-2');

        // Original Name - user's filename
        yield TextField::new('originalName', 'Original File Name')
            ->setMaxLength(Crud::PAGE_INDEX === $pageName ? 40 : 255)
            ->setColumns('col-md-6')
            ->setHelp('Original filename as uploaded by user');

        // File Name - server filename (only on detail)
        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('fileName', 'Stored File Name')
                ->setHelp('Internal filename on server')
                ->onlyOnDetail()
                ->setColumns('col-md-6');
        }

        // MIME Type
        yield TextField::new('mimeType', 'MIME Type')
            ->hideOnIndex()
            ->setColumns('col-md-6');

        // File Size - formatted
        yield IntegerField::new('fileSize', 'Size')
            ->formatValue(function ($value, TaskAttachment $attachment) {
                return sprintf(
                    '<span class="badge badge-info">%s</span>',
                    $attachment->getHumanReadableSize()
                );
            })
            ->setColumns('col-md-2');

        // File Path (only on detail)
        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('filePath', 'File Path')
                ->setHelp('Relative path to file on server')
                ->onlyOnDetail()
                ->setColumns('col-md-12');
        }

        // File Preview/Download (only on detail for images)
        if (Crud::PAGE_DETAIL === $pageName) {
            yield Field::new('filePreview', 'File Preview')
                ->formatValue(function ($value, TaskAttachment $attachment) {
                    if (!$attachment->getFilePath()) {
                        return '<span class="badge badge-warning">File path not available</span>';
                    }

                    $html = '<div class="btn-group mb-3">';

                    // Download button (always available)
                    $html .= sprintf(
                        '<a href="%s" download="%s" class="btn btn-primary" target="_blank">
                            <i class="fa fa-download"></i> Download
                        </a>',
                        htmlspecialchars($attachment->getFilePath()),
                        htmlspecialchars($attachment->getOriginalName())
                    );

                    // Preview button (only for images)
                    if ($attachment->getFileType() === 'image') {
                        $html .= sprintf(
                            '<a href="%s" target="_blank" class="btn btn-info">
                                <i class="fa fa-eye"></i> View Full Size
                            </a>',
                            htmlspecialchars($attachment->getFilePath())
                        );
                    }

                    $html .= '</div>';

                    // Image preview (only for images)
                    if ($attachment->getFileType() === 'image') {
                        $html .= sprintf(
                            '<div class="card" style="max-width: 600px;">
                                <div class="card-body">
                                    <img src="%s" alt="%s" class="img-fluid" style="max-height: 400px;"/>
                                </div>
                                <div class="card-footer text-muted">
                                    <small>%s • %s</small>
                                </div>
                            </div>',
                            htmlspecialchars($attachment->getFilePath()),
                            htmlspecialchars($attachment->getOriginalName()),
                            $attachment->getHumanReadableSize(),
                            $attachment->getMimeType()
                        );
                    } else {
                        // For non-images, show file info card
                        $html .= sprintf(
                            '<div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                <strong>File Information:</strong><br/>
                                Type: %s<br/>
                                Size: %s<br/>
                                MIME: %s
                            </div>',
                            strtoupper($attachment->getFileType()),
                            $attachment->getHumanReadableSize(),
                            $attachment->getMimeType()
                        );
                    }

                    return $html;
                })
                ->onlyOnDetail();
        }

        // Uploaded At
        yield DateTimeField::new('uploadedAt', 'Uploaded At')
            ->setColumns('col-md-4');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Entity filters
            ->add(EntityFilter::new('task', 'Task'))
            ->add(EntityFilter::new('uploadedBy', 'Uploaded By'))

            // File type filter
            ->add(ChoiceFilter::new('fileType', 'File Type')->setChoices([
                'Images' => 'image',
                'Documents' => 'document',
                'Videos' => 'video',
                'Other' => 'other',
            ]))

            // File size filter (in bytes)
            ->add(NumericFilter::new('fileSize', 'File Size (bytes)'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // DISABLE CREATE - attachments are created via API only
            ->disable(Action::NEW)

            // Add view action to index page
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Customize actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action
                ->setIcon('fa fa-edit')
                ->setLabel(false)
                ->displayIf(fn (TaskAttachment $attachment) => false) // Disable edit in index
            )
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action
                ->setIcon('fa fa-trash')
                ->setLabel(false)
            )
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action
                ->setIcon('fa fa-eye')
                ->setLabel(false)
            );
    }

    /**
     * Optimize query with eager loading to prevent N+1 problems
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // Eager load task and uploadedBy to avoid N+1 queries
        $qb->leftJoin('entity.task', 't')
           ->addSelect('t')
           ->leftJoin('entity.uploadedBy', 'u')
           ->addSelect('u');

        return $qb;
    }

    /**
     * Show warning when deleting attachment
     */
    public function deleteEntity($entityManager, $entityInstance): void
    {
        /** @var TaskAttachment $attachment */
        $attachment = $entityInstance;

        $taskTitle = $attachment->getTask() ? $attachment->getTask()->getTitle() : 'Unknown';
        $fileName = $attachment->getOriginalName();

        $this->addFlash('warning', sprintf(
            'Attachment "%s" removed from task "%s". Note: Physical file may still exist on server.',
            $fileName,
            $taskTitle
        ));

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
