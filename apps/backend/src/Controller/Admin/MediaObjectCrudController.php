<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MediaObject;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class MediaObjectCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return MediaObject::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Media Object')
            ->setEntityLabelInPlural('Media Library')
            ->setPageTitle('index', 'System Media Library')
            ->setPageTitle('detail', fn (MediaObject $media) => sprintf(
                'Media: %s',
                $media->getOriginalName()
            ))

            // Pagination - optimized for performance
            ->setPaginatorPageSize(24) // Grid-friendly number
            ->setPaginatorRangeSize(5)

            // Search
            ->setSearchFields(['originalName', 'fileName', 'mimeType', 'uploadedBy.email'])

            // Default sort: newest first
            ->setDefaultSort(['createdAt' => 'DESC'])

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

        // Uploaded By
        yield AssociationField::new('uploadedBy', 'Uploaded By')
            ->setCrudController(UserCrudController::class)
            ->formatValue(function ($value, MediaObject $media) {
                return $media->getUploadedBy() ? $media->getUploadedBy()->getEmail() : '-';
            })
            ->setColumns('col-md-4');

        // File Type - with icon
        yield TextField::new('fileType', 'Type')
            ->formatValue(function ($value, MediaObject $media) {
                $type = $media->getFileType();
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
            ->formatValue(function ($value, MediaObject $media) {
                return sprintf(
                    '<span class="badge badge-info">%s</span>',
                    $media->getHumanReadableSize()
                );
            })
            ->setColumns('col-md-2');

        // File Path (only on detail)
        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('filePath', 'File Path')
                ->setHelp('Relative path to file on server')
                ->onlyOnDetail()
                ->setColumns('col-md-12');

            // Thumbnail Path (only on detail, if exists)
            yield TextField::new('thumbnailPath', 'Thumbnail Path')
                ->setHelp('Relative path to thumbnail (if generated)')
                ->onlyOnDetail()
                ->setColumns('col-md-12');
        }

        // File Preview/Download (only on detail)
        if (Crud::PAGE_DETAIL === $pageName) {
            yield Field::new('filePreview', 'File Preview')
                ->formatValue(function ($value, MediaObject $media) {
                    if (!$media->getFilePath()) {
                        return '<span class="badge badge-warning">File path not available</span>';
                    }

                    $html = '<div class="btn-group mb-3">';

                    // Download button (always available)
                    $html .= sprintf(
                        '<a href="%s" download="%s" class="btn btn-primary" target="_blank">
                            <i class="fa fa-download"></i> Download
                        </a>',
                        htmlspecialchars($media->getFilePath()),
                        htmlspecialchars($media->getOriginalName())
                    );

                    // Preview button (only for images)
                    if ($media->getFileType() === 'image') {
                        $html .= sprintf(
                            '<a href="%s" target="_blank" class="btn btn-info">
                                <i class="fa fa-eye"></i> View Full Size
                            </a>',
                            htmlspecialchars($media->getFilePath())
                        );
                    }

                    $html .= '</div>';

                    // Image preview (only for images)
                    if ($media->getFileType() === 'image') {
                        $imageSrc = $media->getThumbnailPath() ?: $media->getFilePath();
                        $html .= sprintf(
                            '<div class="card" style="max-width: 600px;">
                                <div class="card-body">
                                    <img src="%s" alt="%s" class="img-fluid" style="max-height: 400px;"/>
                                </div>
                                <div class="card-footer text-muted">
                                    <small>%s • %s</small>
                                </div>
                            </div>',
                            htmlspecialchars($imageSrc),
                            htmlspecialchars($media->getOriginalName()),
                            $media->getHumanReadableSize(),
                            $media->getMimeType()
                        );
                    } elseif ($media->getFileType() === 'video') {
                        // Video preview
                        $html .= sprintf(
                            '<div class="card" style="max-width: 600px;">
                                <div class="card-body">
                                    <video controls class="w-100" style="max-height: 400px;">
                                        <source src="%s" type="%s">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                                <div class="card-footer text-muted">
                                    <small>%s • %s</small>
                                </div>
                            </div>',
                            htmlspecialchars($media->getFilePath()),
                            htmlspecialchars($media->getMimeType()),
                            $media->getHumanReadableSize(),
                            $media->getMimeType()
                        );
                    } else {
                        // For non-images/videos, show file info card
                        $html .= sprintf(
                            '<div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                <strong>File Information:</strong><br/>
                                Type: %s<br/>
                                Size: %s<br/>
                                MIME: %s
                            </div>',
                            strtoupper($media->getFileType()),
                            $media->getHumanReadableSize(),
                            $media->getMimeType()
                        );
                    }

                    return $html;
                })
                ->onlyOnDetail();
        }

        // Created At
        yield DateTimeField::new('createdAt', 'Uploaded At')
            ->setColumns('col-md-4');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // Entity filters
            ->add(EntityFilter::new('uploadedBy', 'Uploaded By'))

            // File type filter
            ->add(ChoiceFilter::new('fileType', 'File Type')->setChoices([
                'Images' => 'image',
                'Documents' => 'document',
                'Videos' => 'video',
                'Other' => 'other',
            ]))

            // MIME type filter
            ->add(TextFilter::new('mimeType', 'MIME Type'))

            // File size filter (in bytes)
            ->add(NumericFilter::new('fileSize', 'File Size (bytes)'))

            // Date range filter
            ->add(DateTimeFilter::new('createdAt', 'Upload Date'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // DISABLE CREATE - media objects are created via API only
            ->disable(Action::NEW)

            // Add view action to index page
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Customize actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action
                ->setIcon('fa fa-edit')
                ->setLabel(false)
                ->displayIf(fn (MediaObject $media) => false) // Disable edit in index
            )
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action
                ->setIcon('fa fa-trash')
                ->setLabel('Delete')
                ->displayAsButton()
                ->addCssClass('btn btn-sm btn-danger')
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

        // Eager load uploadedBy to avoid N+1 queries
        $qb->leftJoin('entity.uploadedBy', 'u')
           ->addSelect('u');

        return $qb;
    }

    /**
     * Show warning when deleting media object
     */
    public function deleteEntity($entityManager, $entityInstance): void
    {
        /** @var MediaObject $media */
        $media = $entityInstance;

        $fileName = $media->getOriginalName();

        $this->addFlash('warning', sprintf(
            'Media object "%s" has been removed from the database. Note: Physical file may still exist on server.',
            $fileName
        ));

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
